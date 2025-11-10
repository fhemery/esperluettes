// Minimal emoji dataset for MVP (Unicode only)
export const EMOJIS = [
  { name: 'grinning face', shortname: 'grinning', unicode: '😀', keywords: ['smile', 'happy', 'grin'], category: 'people' },
  { name: 'smiling face with open mouth', shortname: 'smiley', unicode: '😃', keywords: ['smile', 'happy'], category: 'people' },
  { name: 'smiling face with smiling eyes', shortname: 'smile', unicode: '😊', keywords: ['smile', 'blush', 'happy'], category: 'people' },
  { name: 'rolling on the floor laughing', shortname: 'rofl', unicode: '🤣', keywords: ['lol', 'laugh'], category: 'people' },
  { name: 'face with tears of joy', shortname: 'joy', unicode: '😂', keywords: ['joy', 'tears'], category: 'people' },
  { name: 'thinking face', shortname: 'thinking', unicode: '🤔', keywords: ['hmm', 'think'], category: 'people' },
  { name: 'smiling face with heart-eyes', shortname: 'heart_eyes', unicode: '😍', keywords: ['love', 'like'], category: 'people' },
  { name: 'smiling face with open mouth and cold sweat', shortname: 'sweat_smile', unicode: '😅', keywords: ['smile', 'happy'], category: 'people' },
  { name: 'beaming face with smiling eyes', shortname: 'beaming', unicode: '😁', keywords: ['smile', 'laugh'], category: 'people' },
  { name: 'face with open mouth', shortname: 'open_mouth', unicode: '😮', keywords: ['open', 'mouth'], category: 'people' },
  { name: 'winking face', shortname: 'wink', unicode: '😉', keywords: ['wink', 'happy'], category: 'people' },
  { name: 'smiling face with halo', shortname: 'halo', unicode: '😇', keywords: ['halo', 'happy', 'angel'], category: 'people' },
  { name: 'smiling face with hearts', shortname: 'heart_eyes', unicode: '🥰', keywords: ['love', 'like', 'hearts'], category: 'people' },
  { name: 'smiling face with tears', shortname: 'smiling_tears', unicode: '🥲', keywords: ['smiling', 'tears'], category: 'people' },
  { name: 'face savoring food', shortname: 'yum', unicode: '😋', keywords: ['yum', 'tongue'], category: 'people' },
  { name: 'face with rolling eyes', shortname: 'rolling_eyes', unicode: '🙄', keywords: ['rolling', 'eyes'], category: 'people' },
  { name: 'face with raised eyebrow', shortname: 'raised_eyebrow', unicode: '🤨', keywords: ['raised', 'eyebrow'], category: 'people' },
  { name: 'smirking face', shortname: 'smirking', unicode: '😏', keywords: ['smirking', 'eye'], category: 'people' },
  { name: 'relieved face', shortname: 'relieved', unicode: '😌', keywords: ['relieved', 'eye'], category: 'people' },
  { name: 'partying face', shortname: 'partying', unicode: '🥳', keywords: ['partying', 'eye'], category: 'people' },
  { name: 'face with sunglasses', shortname: 'sunglasses', unicode: '😎', keywords: ['sunglasses', 'eye'], category: 'people' },
  { name: 'pleading face', shortname: 'pleading', unicode: '🥺', keywords: ['pleading', 'eye'], category: 'people' },
  { name: 'face screaming in fear', shortname: 'scream', unicode: '😱', keywords: ['scream', 'eye'], category: 'people' },
  { name: 'nerd face', shortname: 'nerd', unicode: '🤓', keywords: ['nerd', 'eye'], category: 'people' },
  { name: 'waving hand', shortname: 'wave', unicode: '👋', keywords: ['wave', 'hand'], category: 'people' },
  { name: 'thumbs up', shortname: 'thumbsup', unicode: '👍', keywords: ['ok', 'good', 'approve'], category: 'people' },
  { name: 'sparkles', shortname: 'sparkles', unicode: '✨', keywords: ['shine', 'new'], category: 'symbols' },
  { name: 'fire', shortname: 'fire', unicode: '🔥', keywords: ['lit', 'hot'], category: 'nature' },
  { name: 'party popper', shortname: 'tada', unicode: '🎉', keywords: ['celebration', 'party'], category: 'activity' },
  { name: 'red heart', shortname: 'heart', unicode: '❤️', keywords: ['love', 'like'], category: 'symbols' },
  { name: 'sparkling heart', shortname: 'sparkling_heart', unicode: '💖', keywords: ['love', 'like'], category: 'symbols' },
  { name: '100% agree', shortname: '100', unicode: '💯', keywords: ['100', 'perfect'], category: 'symbols' },
  { name: 'eyes', shortname: 'eyes', unicode: '👀', keywords: ['eyes'], category: 'people' },
  { name: 'potato', shortname: 'potato', unicode: '🥔', keywords: ['potato'], category: 'food' },
  { name: 'police car light', shortname: 'police', unicode: '🚨', keywords: ['police', 'light'], category: 'people' },
  { name: 'otter', shortname: 'otter', unicode: '🦦', keywords: ['otter'], category: 'nature' },
  { name: 'hourglass not done', shortname: 'hourglass', unicode: '⏳', keywords: ['hourglass', 'not done'], category: 'symbols' },
  { name: 'rainbow', shortname: 'rainbow', unicode: '🌈', keywords: ['rainbow'], category: 'nature' },
  { name: 'thread', shortname: 'thread', unicode: '🧵', keywords: ['thread'], category: 'nature' },
  { name: 'gem', shortname: 'gem', unicode: '💎', keywords: ['gem'], category: 'nature' },
];

export function searchEmojis(query, limit = 24) {
  const q = (query || '').trim().toLowerCase();
  if (!q) return EMOJIS.slice(0, limit);
  const res = EMOJIS.filter(e =>
    e.name.includes(q) ||
    e.shortname.includes(q) ||
    e.keywords.some(k => k.includes(q))
  );
  return res.slice(0, limit);
}
