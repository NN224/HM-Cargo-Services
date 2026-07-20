const fs = require('fs');
const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

apiContent = "import crypto from 'crypto';\n" + apiContent;
apiContent = apiContent.replace("require('crypto').randomBytes", "crypto.randomBytes");

fs.writeFileSync(apiPath, apiContent);
console.log("Patched crypto!");
