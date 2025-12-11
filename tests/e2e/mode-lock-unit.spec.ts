import { test, expect } from '@playwright/test';

const html = `
<!doctype html>
<html><head><meta charset="utf-8"><style>
.mode-selection label.disabled{opacity:.6}
.mode-selection input[type='radio']{appearance:none;-webkit-appearance:none;-moz-appearance:none;width:24px;height:24px;border:2px solid #093b2f;border-radius:50%;background:#fff;position:relative;display:inline-grid;place-content:center}
.mode-selection input[type='radio']::before{content:"";width:12px;height:12px;border-radius:50%;background:#093b2f;transform:scale(0);transition:transform 120ms ease-in-out}
.mode-selection input[type='radio']:checked::before{transform:scale(1)}
</style></head>
<body>
<div class="mode-selection">
  <label><input type="radio" name="mode" value="online"> Online</label>
  <label><input type="radio" name="mode" value="onsite"> Onsite</label>
</div>
<button id="day22" data-mode="onsite">22</button>
<script>
function setLabelDisabled(input, disabled){ if(!input) return; const label = input.closest('label'); if(label){ label.classList.toggle('disabled', !!disabled); } }
function setModeLockUI(mode){
  const online = document.querySelector('input[name="mode"][value="online"]');
  const onsite = document.querySelector('input[name="mode"][value="onsite"]');
  if(!online || !onsite) return;
  online.disabled=false; onsite.disabled=false; setLabelDisabled(online,false); setLabelDisabled(onsite,false);
  if(!mode) return;
  if(mode==='online'){ online.checked=true; onsite.checked=false; onsite.disabled=true; setLabelDisabled(onsite,true); online.dispatchEvent(new Event('change',{bubbles:true})); }
  if(mode==='onsite'){ onsite.checked=true; online.checked=false; online.disabled=true; setLabelDisabled(online,true); onsite.dispatchEvent(new Event('change',{bubbles:true})); }
}

document.getElementById('day22').addEventListener('click', function(){ setModeLockUI(this.dataset.mode); });
</script>
</body></html>`;

for (const mode of ['online', 'onsite'] as const) {
    test(`unit: clicking day 22 with data-mode=${mode} auto-selects`, async ({ page }) => {
        await page.setContent(html.replace('data-mode="onsite"', `data-mode=\"${mode}\"`));
        await page.click('#day22');
        const online = page.locator('input[name="mode"][value="online"]');
        const onsite = page.locator('input[name="mode"][value="onsite"]');
        if (mode === 'online') {
            await expect(online).toBeChecked();
            await expect(onsite).toBeDisabled();
        } else {
            await expect(onsite).toBeChecked();
            await expect(online).toBeDisabled();
        }
    });
}
