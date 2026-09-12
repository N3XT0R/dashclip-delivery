const {chromium}=require('/tmp/dashclip-browser/node_modules/playwright');
const fs=require('node:fs');
(async()=>{
 const browser=await chromium.launch({args:['--no-sandbox']});
 const page=await browser.newPage();
 await page.goto('http://web_sharing/');
 const manifest=JSON.parse(fs.readFileSync('public/build/manifest.json','utf8'));
 await page.evaluate(()=>{
  document.querySelector('main').innerHTML='<form id="zipForm" data-zip-post-url="/browser-zip-check"><input class="pickbox" type="checkbox" value="1"><input class="pickbox" type="checkbox" value="2" disabled><button type="button" id="selectAll">Alle auswählen</button><button type="button" id="selectNone">Alle abwählen</button><button type="button" id="zipSubmit">Download</button><span id="selCount"></span></form>';
 });
 await page.addScriptTag({type:'module',url:'/build/'+manifest['resources/js/offers.js'].file});
 await page.waitForFunction(()=>document.querySelector('#selCount').textContent==='0 ausgewählt');
 await page.locator('#selectAll').click();
 if(await page.locator('.pickbox:checked').count()!==1) throw Error('Disabled clips selected');
 const requests=[];
 await page.route('**/browser-zip-check',route=>{requests.push(route.request().postDataJSON()); return route.fulfill({json:{jobId:'browser-check'}})});
 await page.locator('#zipSubmit').click();
 await page.waitForTimeout(250);
 if(requests.length!==1 || JSON.stringify(requests[0].assignment_ids)!=='["1"]') throw Error('Wrong ZIP payload or duplicate initialization');
 if(!await page.locator('#downloadModal').isVisible()) throw Error('Download progress not shown');
 console.log(JSON.stringify({disabledSelection:true,singleInitialization:true,zipPayload:requests[0],progressVisible:true}));
 await browser.close();
})();
