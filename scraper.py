import argparse
import asyncio
import os
import sys
from datetime import datetime

import pandas as pd
from tqdm import tqdm

from ai_enricher import generate_personalization
from maps_scraper import scrape_google_maps
from website_scraper import scrape_website

OUTPUT_DIR = os.path.join(os.path.dirname(__file__), "output")

CSV_COLUMNS = [
    "name",
    "phone",
    "address",
    "website",
    "rating",
    "reviews",
    "emails",
    "linkedin",
    "instagram",
    "facebook",
    "twitter",
    "description",
    "personalization",
    "maps_url",
]


def build_output_path(query: str, location: str, custom: str | None) -> str:
    os.makedirs(OUTPUT_DIR, exist_ok=True)
    if custom:
        filename = custom if custom.endswith(".csv") else custom + ".csv"
        return os.path.join(OUTPUT_DIR, filename)
    slug = f"{query}_{location}".lower()
    slug = "".join(c if c.isalnum() else "_" for c in slug)
    slug = "_".join(filter(None, slug.split("_")))
    timestamp = datetime.now().strftime("%Y%m%d_%H%M%S")
    return os.path.join(OUTPUT_DIR, f"{slug}_{timestamp}.csv")


def parse_args():
    parser = argparse.ArgumentParser(description="Google Maps business scraper")
    parser.add_argument("--query", required=True, help='Search query, e.g. "dentists"')
    parser.add_argument("--location", required=True, help='Location, e.g. "Istanbul, Turkey"')
    parser.add_argument("--max-results", type=int, default=20, help="Max number of businesses to scrape (default: 20)")
    parser.add_argument("--output", default=None, help="Output CSV filename (default: auto-generated)")
    parser.add_argument("--no-website", action="store_true", help="Skip website scraping")
    parser.add_argument("--no-ai", action="store_true", help="Skip AI personalization (requires ANTHROPIC_API_KEY)")
    parser.add_argument("--no-headless", action="store_true", help="Show browser window (default: headless)")
    return parser.parse_args()


async def main():
    args = parse_args()

    full_query = f"{args.query} in {args.location}"
    headless = not args.no_headless
    output_path = build_output_path(args.query, args.location, args.output)

    use_ai = not args.no_ai and not args.no_website
    if use_ai and not os.environ.get("ANTHROPIC_API_KEY"):
        print("[!] ANTHROPIC_API_KEY not set — AI personalization will be skipped.")
        print("    Set it with: export ANTHROPIC_API_KEY=your-key-here\n")
        use_ai = False

    print(f"[*] Query:          {full_query}")
    print(f"[*] Max results:    {args.max_results}")
    print(f"[*] Headless:       {headless}")
    print(f"[*] AI enrichment:  {'enabled' if use_ai else 'disabled'}")
    print(f"[*] Output:         {output_path}")
    print()

    print("[*] Starting Google Maps scrape...")
    businesses = await scrape_google_maps(full_query, max_results=args.max_results, headless=headless)
    print(f"[+] Found {len(businesses)} businesses on Google Maps\n")

    if not businesses:
        print("[!] No results found. Exiting.")
        sys.exit(0)

    rows = []

    if args.no_website:
        for biz in businesses:
            row = {col: biz.get(col) for col in CSV_COLUMNS}
            row["emails"] = ""
            row["linkedin"] = None
            row["instagram"] = None
            row["facebook"] = None
            row["twitter"] = None
            row["description"] = None
            row["personalization"] = None
            rows.append(row)
    else:
        step_label = "Scraping websites + generating AI personalizations" if use_ai else "Scraping business websites"
        print(f"[*] {step_label}...")
        with tqdm(total=len(businesses), unit="biz") as pbar:
            for biz in businesses:
                website = biz.get("website")
                web_data = {}
                if website:
                    web_data = scrape_website(website)

                row = {col: biz.get(col) for col in CSV_COLUMNS}
                emails_list = web_data.get("emails", [])
                row["emails"] = ";".join(emails_list) if emails_list else ""
                row["linkedin"] = web_data.get("linkedin")
                row["instagram"] = web_data.get("instagram")
                row["facebook"] = web_data.get("facebook")
                row["twitter"] = web_data.get("twitter")
                row["description"] = web_data.get("description")

                personalization = None
                if use_ai:
                    personalization = generate_personalization(
                        business_name=biz.get("name", ""),
                        search_query=args.query,
                        location=args.location,
                        description=web_data.get("description"),
                        page_text=web_data.get("page_text"),
                        rating=biz.get("rating"),
                        reviews=biz.get("reviews"),
                    )
                row["personalization"] = personalization

                rows.append(row)
                pbar.update(1)
        print()

    df = pd.DataFrame(rows, columns=CSV_COLUMNS)
    df.to_csv(output_path, index=False, encoding="utf-8-sig")

    total = len(df)
    with_email = df["emails"].apply(lambda x: bool(x and str(x).strip())).sum()
    with_website = df["website"].notna().sum()
    with_phone = df["phone"].notna().sum()
    with_personalization = df["personalization"].notna().sum() if "personalization" in df.columns else 0

    print(f"[+] Done! Results saved to: {output_path}")
    print(f"    Total businesses    : {total}")
    print(f"    With website        : {with_website}")
    print(f"    With phone          : {with_phone}")
    print(f"    With email          : {with_email}")
    if use_ai:
        print(f"    With personalization: {with_personalization}")


if __name__ == "__main__":
    asyncio.run(main())
