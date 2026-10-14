import { chromium } from 'playwright-core';
import path from 'path';

async function captureScreenshots() {
    console.log('📸 Capturing UI & Dashboard Screenshots for README documentation...\n');

    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true,
    });

    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
        deviceScaleFactor: 2, // Retina quality
    });

    const page = await context.newPage();

    try {
        // 1. Restaurant Menu Page
        console.log('1. Capturing Restaurant Menu...');
        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });
        await page.waitForTimeout(600);
        await page.screenshot({ path: 'docs/images/01-restaurant-menu.png' });
        console.log('   ✓ Saved docs/images/01-restaurant-menu.png');

        // 2. Add items to cart & open cart drawer
        console.log('2. Capturing Cart Drawer...');
        await page.locator('button:has-text("Add")').first().click();
        await page.waitForTimeout(400);
        await page.locator('button:has-text("Add")').nth(1).click();
        await page.waitForTimeout(400);

        // Open cart
        await page.locator('button:has(.fa-basket-shopping)').first().click();
        await page.waitForTimeout(600);

        // Fill form fields
        await page.fill('#cust-name', 'Alex Morgan');
        await page.fill('#cust-email', 'alex.morgan@example.com');
        await page.fill('#cust-table', 'Table 04');
        await page.fill('#cust-phone', '+1 555-0192');
        await page.waitForTimeout(400);

        await page.screenshot({ path: 'docs/images/02-cart-checkout.png' });
        console.log('   ✓ Saved docs/images/02-cart-checkout.png');

        // 3. Submit Order & Capture Order Confirmation Modal
        console.log('3. Capturing Order Success Modal...');
        await page.click('#btn-place-order');
        await page.waitForSelector('#order-success-modal:not(.opacity-0)', { timeout: 8000 });
        await page.waitForTimeout(500);
        await page.screenshot({ path: 'docs/images/03-order-confirmed.png' });
        console.log('   ✓ Saved docs/images/03-order-confirmed.png');

        // 4. Analytics Dashboard
        console.log('4. Capturing Analytics Dashboard...');
        await page.goto('http://127.0.0.1:8000/analytics', { waitUntil: 'networkidle' });
        await page.waitForTimeout(2000); // Allow Chart.js animations to complete
        await page.screenshot({ path: 'docs/images/04-analytics-dashboard.png' });
        console.log('   ✓ Saved docs/images/04-analytics-dashboard.png');

        console.log('\n🎉 All screenshots captured successfully in /docs/images/!');
    } catch (err) {
        console.error('Screenshot capture failed:', err);
    } finally {
        await browser.close();
    }
}

captureScreenshots();
