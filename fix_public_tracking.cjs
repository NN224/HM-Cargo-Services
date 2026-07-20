const fs = require('fs');
const file = 'src/client/pages/PublicTracking.tsx';
let content = fs.readFileSync(file, 'utf8');

// Fix the backticks and dollars that got escaped
content = content.replace(/\\`/g, '`');
content = content.replace(/\\\$/g, '$');
fs.writeFileSync(file, content);
console.log("Fixed syntax");
