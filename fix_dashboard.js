const fs = require('fs');
const file = 'src/client/pages/Dashboard.tsx';
let content = fs.readFileSync(file, 'utf8');
content = content.replace('.then(setData)', `.then(data => {
        if (data && data.latestShipments) {
          setData(data);
        }
      })`);
fs.writeFileSync(file, content);
