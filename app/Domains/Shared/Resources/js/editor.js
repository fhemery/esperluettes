import { EditorState } from 'prosemirror-state'
import { EditorView } from 'prosemirror-view'
import { Schema, DOMParser, DOMSerializer } from 'prosemirror-model'
import { schema } from 'prosemirror-schema-basic'
import { addListNodes } from 'prosemirror-schema-list'
import { keymap } from 'prosemirror-keymap'
import { history, undo, redo } from 'prosemirror-history'
import { baseKeymap, toggleMark, setBlockType, wrapIn } from 'prosemirror-commands'

// Create schema with basic marks and list support
const mySchema = new Schema({
    nodes: addListNodes(schema.spec.nodes, 'paragraph block*', 'block'),
    marks: schema.spec.marks
})

function initProseMirrorEditor(element, options = {}) {
    const {
        content = '',
        placeholder = 'Start writing...',
        maxCharacters = 1000,
        onUpdate = () => {},
        onCharacterCountUpdate = () => {}
    } = options;

    // Parse initial content
    let doc
    if (content) {
        const tempDiv = document.createElement('div')
        tempDiv.innerHTML = content
        doc = DOMParser.fromSchema(mySchema).parse(tempDiv)
    } else {
        doc = mySchema.nodes.doc.create(mySchema.nodes.paragraph.create())
    }

    // Create editor state
    const state = EditorState.create({
        doc,
        plugins: [
            history(),
            keymap({
                'Mod-z': undo,
                'Mod-y': redo,
                'Mod-b': toggleMark(mySchema.marks.strong),
                'Mod-i': toggleMark(mySchema.marks.em),
                'Mod-Shift-8': wrapIn(mySchema.nodes.bullet_list),
                'Mod-Shift-9': wrapIn(mySchema.nodes.ordered_list),
            }),
            keymap(baseKeymap)
        ]
    })

    // Create editor view
    const view = new EditorView(element, {
        state,
        dispatchTransaction(transaction) {
            const newState = view.state.apply(transaction)
            view.updateState(newState)
            
            // Get HTML content
            const serializer = DOMSerializer.fromSchema(mySchema)
            const fragment = serializer.serializeFragment(newState.doc.content)
            const div = document.createElement('div')
            div.appendChild(fragment)
            const html = div.innerHTML
            
            // Count characters (text only)
            const textContent = newState.doc.textContent
            const characterCount = textContent.length
            
            onUpdate(html)
            onCharacterCountUpdate(characterCount)
        },
        attributes: {
            class: 'prose prose-sm focus:outline-none min-h-[120px] p-4 border border-gray-300 rounded-lg',
            style: 'white-space: pre-wrap;'
        }
    })

    return {
        view,
        getHTML: () => {
            const serializer = DOMSerializer.fromSchema(mySchema)
            const fragment = serializer.serializeFragment(view.state.doc.content)
            const div = document.createElement('div')
            div.appendChild(fragment)
            return div.innerHTML
        },
        getText: () => view.state.doc.textContent,
        getCharacterCount: () => view.state.doc.textContent.length,
        setContent: (html) => {
            const tempDiv = document.createElement('div')
            tempDiv.innerHTML = html
            const doc = DOMParser.fromSchema(mySchema).parse(tempDiv)
            const newState = EditorState.create({
                doc,
                plugins: view.state.plugins
            })
            view.updateState(newState)
        },
        focus: () => view.focus(),
        destroy: () => view.destroy(),
        toggleBold: () => {
            const command = toggleMark(mySchema.marks.strong)
            command(view.state, view.dispatch)
        },
        toggleItalic: () => {
            const command = toggleMark(mySchema.marks.em)
            command(view.state, view.dispatch)
        },
        toggleBulletList: () => {
            const command = wrapIn(mySchema.nodes.bullet_list)
            command(view.state, view.dispatch)
        },
        toggleOrderedList: () => {
            const command = wrapIn(mySchema.nodes.ordered_list)
            command(view.state, view.dispatch)
        },
        isActive: (markOrNode) => {
            const { from, to } = view.state.selection
            if (mySchema.marks[markOrNode]) {
                return mySchema.marks[markOrNode].isInSet(view.state.storedMarks || view.state.selection.$from.marks())
            }
            return false
        }
    }
}

// Alpine.js component for ProseMirror integration
export function proseMirrorComponent() {
    return {
        editor: null,
        characterCount: 0,
        maxCharacters: 1000,
        content: '',
        
        init() {
            console.log('init prosemirror')
            this.$nextTick(() => {
                const editorElement = this.$refs.editor;
                const hiddenInput = this.$refs.hiddenInput;
                
                this.editor = initProseMirrorEditor(editorElement, {
                    content: this.content,
                    placeholder: 'Tell us about yourself... You can use bold, italic, and other basic formatting.',
                    maxCharacters: this.maxCharacters,
                    onUpdate: (html) => {
                        hiddenInput.value = html;
                        this.content = html;
                    },
                    onCharacterCountUpdate: (count) => {
                        this.characterCount = count;
                    }
                });
                
                // Set initial character count
                this.characterCount = this.editor.getCharacterCount();
            });
        },
        
        destroy() {
            if (this.editor) {
                this.editor.destroy();
            }
        },
        
        get isOverLimit() {
            return this.characterCount > this.maxCharacters;
        },
        
        get characterCountClass() {
            if (this.characterCount > this.maxCharacters * 0.9) {
                return this.isOverLimit ? 'text-red-600' : 'text-yellow-600';
            }
            return 'text-gray-500';
        }
    };
}
