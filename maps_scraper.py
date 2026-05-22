import asyncio
import random
from playwright.async_api import async_playwright


async def _random_delay(min_s=0.5, max_s=2.0):
    await asyncio.sleep(random.uniform(min_s, max_s))


async def scrape_google_maps(query: str, max_results: int = 20, headless: bool = True) -> list[dict]:
    results = []

    async with async_playwright() as p:
        browser = await p.chromium.launch(headless=headless)
        context = await browser.new_context(
            viewport={"width": 1280, "height": 800},
            user_agent=(
                "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
                "AppleWebKit/537.36 (KHTML, like Gecko) "
                "Chrome/120.0.0.0 Safari/537.36"
            ),
        )
        page = await context.new_page()

        search_url = f"https://www.google.com/maps/search/{query.replace(' ', '+')}"
        await page.goto(search_url, wait_until="domcontentloaded")
        await _random_delay(2, 4)

        # Accept cookies if prompted
        try:
            accept_btn = page.locator('button:has-text("Accept all"), button:has-text("Reject all"), button[aria-label*="Accept"]')
            if await accept_btn.first.is_visible(timeout=3000):
                await accept_btn.first.click()
                await _random_delay()
        except Exception:
            pass

        # Wait for results panel
        results_selector = '[role="feed"], .m6QErb[aria-label]'
        try:
            await page.wait_for_selector(results_selector, timeout=15000)
        except Exception:
            await browser.close()
            return results

        # Scroll results list to load more
        feed = page.locator('[role="feed"]').first
        last_count = 0
        stale_iterations = 0

        while len(results) < max_results and stale_iterations < 5:
            cards = page.locator('[role="feed"] > div > div[jsaction]')
            current_count = await cards.count()

            if current_count == last_count:
                stale_iterations += 1
            else:
                stale_iterations = 0
                last_count = current_count

            if current_count >= max_results:
                break

            try:
                await feed.evaluate("el => el.scrollBy(0, 600)")
            except Exception:
                try:
                    await page.keyboard.press("End")
                except Exception:
                    pass
            await _random_delay(0.8, 1.5)

        # Collect business detail URLs
        cards = page.locator('[role="feed"] > div > div[jsaction]')
        total = min(await cards.count(), max_results)

        for i in range(total):
            try:
                card = cards.nth(i)
                await card.scroll_into_view_if_needed()
                await _random_delay(0.3, 0.8)
                await card.click()
                await _random_delay(1.5, 3.0)

                # Wait for detail panel
                try:
                    await page.wait_for_selector('h1[class*="DUwDvf"], h1.fontHeadlineLarge', timeout=8000)
                except Exception:
                    await page.wait_for_selector('[data-item-id], [data-section-id="overview"]', timeout=5000)

                data = await _extract_business_data(page)
                if data:
                    results.append(data)

                # Navigate back
                try:
                    back_btn = page.locator('button[aria-label*="Back"], button[jsaction*="back"], button.hYBOP')
                    if await back_btn.first.is_visible(timeout=2000):
                        await back_btn.first.click()
                        await _random_delay(1.0, 2.0)
                    else:
                        await page.go_back()
                        await _random_delay(1.0, 2.0)
                except Exception:
                    await page.go_back()
                    await _random_delay(1.0, 2.0)

            except Exception:
                continue

        await browser.close()

    return results


async def _extract_business_data(page) -> dict:
    data = {
        "name": None,
        "phone": None,
        "address": None,
        "website": None,
        "rating": None,
        "reviews": None,
        "maps_url": page.url,
    }

    # Name
    for selector in ['h1[class*="DUwDvf"]', 'h1.fontHeadlineLarge', 'h1']:
        try:
            el = page.locator(selector).first
            if await el.is_visible(timeout=2000):
                data["name"] = (await el.text_content()).strip()
                break
        except Exception:
            continue

    # Rating
    for selector in [
        'span[aria-label*="stars"], span[aria-label*="star"]',
        'div[jsaction*="rating"] span.MW4etd',
        'span.MW4etd',
    ]:
        try:
            el = page.locator(selector).first
            if await el.is_visible(timeout=1000):
                text = (await el.text_content()).strip()
                data["rating"] = float(text.replace(",", "."))
                break
        except Exception:
            continue

    # Review count
    for selector in [
        'span[aria-label*="reviews"]',
        'button[jsaction*="reviews"] span',
        'span.UY7F9',
    ]:
        try:
            el = page.locator(selector).first
            if await el.is_visible(timeout=1000):
                text = (await el.text_content()).strip()
                import re
                nums = re.findall(r"[\d,]+", text)
                if nums:
                    data["reviews"] = int(nums[0].replace(",", ""))
                    break
        except Exception:
            continue

    # Address, Phone, Website — from info buttons
    info_selectors = [
        'button[data-item-id*="address"]',
        'button[data-tooltip="Copy address"]',
        '[data-item-id*="address"] .Io6YTe',
        'div[data-section-id="ad"] button',
    ]

    # Generic approach: iterate all info-block buttons
    try:
        info_items = page.locator('[data-item-id]')
        count = await info_items.count()
        import re
        for idx in range(count):
            item = info_items.nth(idx)
            try:
                item_id = await item.get_attribute("data-item-id") or ""
                aria = await item.get_attribute("aria-label") or ""
                text = (await item.text_content() or "").strip()

                if "address" in item_id.lower() or "address" in aria.lower():
                    if text and data["address"] is None:
                        data["address"] = text

                elif "phone" in item_id.lower() or "phone" in aria.lower() or re.search(r"\+?\d[\d\s\-().]{6,}", text):
                    if text and data["phone"] is None and re.search(r"\+?\d[\d\s\-().]{6,}", text):
                        data["phone"] = text

                elif "website" in item_id.lower() or "website" in aria.lower():
                    href = await item.get_attribute("href")
                    if href and href.startswith("http") and data["website"] is None:
                        data["website"] = href
                    elif text and text.startswith("http") and data["website"] is None:
                        data["website"] = text
            except Exception:
                continue
    except Exception:
        pass

    # Fallback website from anchor tags in detail panel
    if data["website"] is None:
        try:
            links = page.locator('a[data-item-id*="authority"], a[href*="://"][data-item-id]')
            c = await links.count()
            for li in range(c):
                href = await links.nth(li).get_attribute("href") or ""
                if href.startswith("http") and "google.com" not in href and "maps" not in href:
                    data["website"] = href
                    break
        except Exception:
            pass

    # Fallback phone
    if data["phone"] is None:
        try:
            import re
            phone_el = page.locator('[data-item-id*="phone"] .Io6YTe, [aria-label*="Phone"] .Io6YTe').first
            if await phone_el.is_visible(timeout=1000):
                data["phone"] = (await phone_el.text_content()).strip()
        except Exception:
            pass

    # Fallback address
    if data["address"] is None:
        try:
            addr_el = page.locator('[data-item-id*="address"] .Io6YTe, [aria-label*="Address"] .Io6YTe').first
            if await addr_el.is_visible(timeout=1000):
                data["address"] = (await addr_el.text_content()).strip()
        except Exception:
            pass

    return data
