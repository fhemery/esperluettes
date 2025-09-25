export function BadgeOverflow(overflowId) {
    return {
        count: 0,
        init() {
            this.$nextTick(() => {
                try {
                    const hidden = this.$refs.hidden;
                    const visible = this.$refs.visible;
                    const container = this.$refs.container;
                    const overflow = document.getElementById(overflowId);
                    this.count = (hidden && hidden.children) ? hidden.children.length : 0;

                    // Move items one by one; if visible overflows after appending, move item to overflow and do NOT decrease count
                    while (hidden && hidden.firstElementChild) {
                        const el = hidden.firstElementChild;
                        visible.appendChild(el);
                        // Check overflow
                        const fits = visible.scrollWidth <= visible.clientWidth + 0.5;
                        if (fits) {
                            this.count--
                        } else {
                            overflow.appendChild(el);
                        }
                    }

                    // If count === 0, we have successfully moved all items to visible, so remove the badge responsible for overflow
                    // Else, add it back, but to the end instead                    
                    if (this.count === 0) {
                        container.removeChild(container.firstElementChild);
                    } else {
                        visible.appendChild(container.firstElementChild);
                    }
                } catch (_) {
                    this.count = 0;
                }
            });
        }
    }
}