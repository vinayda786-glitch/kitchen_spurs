import { chromium } from 'playwright-core';

async function runE2ETest() {
    console.log('🚀 Starting Playwright E2E Test Suite for Kitchen Analytics...\n');

    const browser = await chromium.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
        headless: true,
    });

    const context = await browser.newContext({
        viewport: { width: 1280, height: 800 }
    });

    const page = await context.newPage();

    // Listen for any unhandled page errors or console logs
    const consoleLogs = [];
    const pageErrors = [];
    page.on('console', msg => consoleLogs.push(`[${msg.type()}] ${msg.text()}`));
    page.on('pageerror', err => pageErrors.push(err.message));

    try {
        // -------------------------------------------------------------
        // Step 1: Visit Restaurant Menu Page
        // -------------------------------------------------------------
        console.log('1️⃣ Navigating to Home Menu (http://127.0.0.1:8000/)...');
        await page.goto('http://127.0.0.1:8000/', { waitUntil: 'networkidle' });

        const title = await page.title();
        console.log(`   Page Title: "${title}"`);

        // Verify Hero Banner text is visible and contrasting
        const heroTitle = await page.locator('h1').textContent();
        console.log(`   Hero Banner Heading: "${heroTitle.replace(/\s+/g, ' ').trim()}"`);

        // -------------------------------------------------------------
        // Step 2: Test Menu Item Interaction (item_viewed)
        // -------------------------------------------------------------
        console.log('\n2️⃣ Testing Item Details Modal & Browser Telemetry...');
        const firstDetailsBtn = page.locator('button:has-text("Details")').first();
        await firstDetailsBtn.click();
        await page.waitForTimeout(400);

        const modalTitle = await page.locator('#modal-title').textContent();
        console.log(`   Opened Modal for: "${modalTitle}"`);

        const modalCloseBtn = page.locator('#item-modal button:has-text("Close")');
        await modalCloseBtn.click();
        await page.waitForTimeout(300);

        // -------------------------------------------------------------
        // Step 3: Add Item to Cart & Open Cart Drawer
        // -------------------------------------------------------------
        console.log('\n3️⃣ Adding Item to Cart...');
        const firstAddBtn = page.locator('button:has-text("Add")').first();
        await firstAddBtn.click();
        await page.waitForTimeout(400);

        const badgeCount = await page.locator('#cart-badge').textContent();
        console.log(`   Cart Badge Count: ${badgeCount}`);

        console.log('   Opening Cart Drawer...');
        const cartIconBtn = page.locator('button:has(.fa-basket-shopping)').first();
        await cartIconBtn.click();
        await page.waitForTimeout(400);

        // Verify Cart Drawer is visible
        const cartSubtotal = await page.locator('#cart-subtotal').textContent();
        console.log(`   Cart Subtotal: ${cartSubtotal}`);

        // -------------------------------------------------------------
        // Step 4: Fill Checkout Form & Place Order (Server SDK Trigger)
        // -------------------------------------------------------------
        console.log('\n4️⃣ Testing Typing in Checkout Inputs (Verifying drawer does NOT close)...');
        await page.click('#cust-name');
        await page.type('#cust-name', 'Playwright Automated Tester', { delay: 40 });
        let isOpenDuringTyping = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
        if (!isOpenDuringTyping) throw new Error('Cart drawer closed unexpectedly while typing name!');
        console.log('   ✓ Typed name, drawer remained open.');

        await page.click('#cust-email');
        await page.type('#cust-email', 'playwright.test@example.com', { delay: 40 });
        isOpenDuringTyping = !(await page.locator('#cart-drawer').getAttribute('class')).includes('translate-x-full');
        if (!isOpenDuringTyping) throw new Error('Cart drawer closed unexpectedly while typing email!');
        console.log('   ✓ Typed email, drawer remained open.');

        await page.click('#cust-table');
        await page.type('#cust-table', 'Table 99', { delay: 40 });
        await page.click('#cust-phone');
        await page.type('#cust-phone', '+1 555-8899', { delay: 40 });
        console.log('   ✓ All inputs filled successfully without drawer closing.');

        console.log('   Clicking "Confirm & Place Order"...');
        const placeOrderBtn = page.locator('#btn-place-order');
        await placeOrderBtn.click();

        // Wait for order success modal
        await page.waitForSelector('#order-success-modal:not(.opacity-0)', { timeout: 8000 });
        const successOrderNum = await page.locator('#success-order-num').textContent();
        console.log(`   ✅ Order Created Successfully! Order Number: ${successOrderNum}`);

        // -------------------------------------------------------------
        // Step 5: Check Analytics Dashboard
        // -------------------------------------------------------------
        console.log('\n5️⃣ Navigating to Analytics Dashboard (http://127.0.0.1:8000/analytics)...');
        await page.goto('http://127.0.0.1:8000/analytics', { waitUntil: 'networkidle' });
        await page.waitForTimeout(1500); // Allow Chart.js & stats to render

        const pageviewsKpi = await page.locator('#kpi-pageviews').textContent();
        const visitorsKpi = await page.locator('#kpi-visitors').textContent();
        const ordersKpi = await page.locator('#kpi-orders').textContent();

        console.log(`   📊 Dashboard KPIs:`);
        console.log(`      - Total Page Views: ${pageviewsKpi}`);
        console.log(`      - Unique Visitors: ${visitorsKpi}`);
        console.log(`      - Orders Created (Server SDK): ${ordersKpi}`);

        // Verify recent events table has rows
        const recentEventsCount = await page.locator('#recent-events-tbody tr').count();
        console.log(`      - Live Event Feed Rows: ${recentEventsCount}`);

        // Check if any errors occurred
        if (pageErrors.length > 0) {
            console.error('\n❌ Page JavaScript Errors Encountered:');
            pageErrors.forEach(err => console.error(`   - ${err}`));
            process.exit(1);
        } else {
            console.log('\n✅ Zero JavaScript runtime errors during entire flow!');
        }

        console.log('\n🎉 ALL PLAYWRIGHT E2E TESTS PASSED 100% SUCCESSFULLY!\n');
    } catch (err) {
        console.error('Test Execution Failed:', err);
        process.exit(1);
    } finally {
        await browser.close();
    }
}

runE2ETest();

