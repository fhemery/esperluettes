window.countdownTimer = {
    init() {
        this.endTime = new Date(this.$el.dataset.endTime);
        this.updateInterval = parseInt(this.$el.dataset.updateInterval);
        this.showSeconds = this.$el.dataset.showSeconds === 'true';
        this.translations = {
            day: this.$el.dataset.transDay,
            days: this.$el.dataset.transDays,
            hour: this.$el.dataset.transHour,
            hours: this.$el.dataset.transHours,
            minute: this.$el.dataset.transMinute,
            minutes: this.$el.dataset.transMinutes,
            second: this.$el.dataset.transSecond,
            seconds: this.$el.dataset.transSeconds,
            separator: this.$el.dataset.transSeparator,
            finished: this.$el.dataset.transFinished
        };
        
        this.update();
        this.timer = setInterval(() => this.update(), this.updateInterval);
    },
    
    update() {
        const now = new Date();
        const diff = this.endTime - now;
        
        if (diff <= 0) {
            this.$el.textContent = this.translations.finished;
            clearInterval(this.timer);
            return;
        }
        
        const days = Math.floor(diff / (1000 * 60 * 60 * 24));
        const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((diff % (1000 * 60)) / 1000);
        
        const parts = [];
        
        if (days > 0) {
            parts.push(`${days} ${this.pluralize(days, this.translations.day, this.translations.days)}`);
        }
        
        if (hours > 0 || days > 0) {
            parts.push(`${hours} ${this.pluralize(hours, this.translations.hour, this.translations.hours)}`);
        }
        
        if (minutes > 0 || hours > 0 || days > 0) {
            parts.push(`${minutes} ${this.pluralize(minutes, this.translations.minute, this.translations.minutes)}`);
        }
        
        if (this.showSeconds && (seconds > 0 || (days === 0 && hours === 0 && minutes === 0))) {
            parts.push(`${seconds} ${this.pluralize(seconds, this.translations.second, this.translations.seconds)}`);
        }
        
        this.$el.textContent = parts.join(this.translations.separator);
    },
    
    pluralize(count, singular, plural) {
        return count === 1 ? singular : plural;
    },
    
    destroy() {
        if (this.timer) {
            clearInterval(this.timer);
        }
    }
};
