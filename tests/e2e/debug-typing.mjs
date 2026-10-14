import { chromium } from 'playwright-core';

async function testTyping() {
    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true,
    });
    const page = await browser.newPage();
    page.on('console', msg => console.log('LOG:', msg.text()));
    page.on('pageerror', err => console.error('ERROR:', err));

    await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });

    // Add item to cart
    await page.locator('button:has-text("Add")').first().click();
    await page.waitForTimeout(400);

    // Open cart drawer
    await page.locator('button:has(.fa-basket-shopping)').first().click();
    await page.waitForTimeout(500);

    const isCartOpenBefore = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
    console.log('1. Cart drawer open before typing?', isCartOpenBefore);

    // Click into #cust-name and type
    console.log('2. Clicking into #cust-name and typing...');
    await page.click('#cust-name');
    await page.type('#cust-name', 'Vinay Gupta', { delay: 100 });
    await page.waitForTimeout(500);

    const isCartOpenAfterName = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
    console.log('   Cart drawer open after typing name?', isCartOpenAfterName);

    // Click into #cust-email and type
    console.log('3. Clicking into #cust-email and typing...');
    await page.click('#cust-email');
    await page.type('#cust-email', 'vaibhav1792@yopmail.com', { delay: 100 });
    await page.waitForTimeout(500);

    const isCartOpenAfterEmail = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
    console.log('   Cart drawer open after typing email?', isCartOpenAfterEmail);

    // Press Tab
    console.log('4. Pressing Tab to blur email and focus Table...');
    await page.keyboard.press('Tab');
    await page.waitForTimeout(500);

    const isCartOpenAfterTab = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
    console.log('   Cart drawer open after Tab?', isCartOpenAfterTab);

    // Type in Table
    console.log('5. Typing in #cust-table...');
    await page.type('#cust-table', '2', { delay: 100 });
    await page.waitForTimeout(500);

    const isCartOpenAfterTable = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
    console.log('   Cart drawer open after typing table?', isCartOpenAfterTable);

    await browser.close();
}

testTyping();

