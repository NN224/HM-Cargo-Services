const fs = require('fs');
const apiPath = 'src/server/api.ts';
let apiContent = fs.readFileSync(apiPath, 'utf8');

const oldPost = `const { trackingNumber, recipientName, recipientPhone, customerId, packages: pkgList } = req.body;`;
const newPost = `const { recipientName, recipientPhone, customerId, packages: pkgList } = req.body;
  const trackingNumber = req.body.trackingNumber || require('crypto').randomBytes(16).toString('hex');`;

apiContent = apiContent.replace(oldPost, newPost);

fs.writeFileSync(apiPath, apiContent);
console.log("Patched API tracking number generation!");
