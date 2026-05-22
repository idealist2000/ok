import re
import requests
from bs4 import BeautifulSoup

EMAIL_REGEX = re.compile(r"[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}")
ASSET_EXTENSIONS = re.compile(r"\.(png|jpg|jpeg|gif|svg|ico|bmp|webp|css|js|woff|ttf)$", re.IGNORECASE)

SOCIAL_DOMAINS = {
    "linkedin": ["linkedin.com"],
    "instagram": ["instagram.com"],
    "facebook": ["facebook.com", "fb.com"],
    "twitter": ["twitter.com", "x.com"],
}

HEADERS = {
    "User-Agent": (
        "Mozilla/5.0 (Windows NT 10.0; Win64; x64) "
        "AppleWebKit/537.36 (KHTML, like Gecko) "
        "Chrome/120.0.0.0 Safari/537.36"
    ),
    "Accept": "text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
    "Accept-Language": "en-US,en;q=0.5",
}


def _clean_emails(raw: set) -> list[str]:
    cleaned = []
    for email in raw:
        if ASSET_EXTENSIONS.search(email):
            continue
        if len(email) > 254:
            continue
        cleaned.append(email.lower())
    return sorted(set(cleaned))


def _extract_emails_from_text(text: str) -> set:
    return set(EMAIL_REGEX.findall(text))


def _extract_social_links(soup: BeautifulSoup) -> dict:
    found = {k: None for k in SOCIAL_DOMAINS}
    for a in soup.find_all("a", href=True):
        href = a["href"].lower()
        for platform, domains in SOCIAL_DOMAINS.items():
            if found[platform] is None:
                for domain in domains:
                    if domain in href:
                        found[platform] = a["href"]
                        break
    return found


def _extract_description(soup: BeautifulSoup) -> str | None:
    meta = soup.find("meta", attrs={"name": "description"})
    if meta and meta.get("content", "").strip():
        return meta["content"].strip()

    og_desc = soup.find("meta", attrs={"property": "og:description"})
    if og_desc and og_desc.get("content", "").strip():
        return og_desc["content"].strip()

    for tag in ["p", "div"]:
        for el in soup.find_all(tag):
            text = el.get_text(separator=" ", strip=True)
            if len(text) >= 80:
                return text[:500]

    return None


def scrape_website(url: str) -> dict:
    empty = {
        "emails": [],
        "linkedin": None,
        "instagram": None,
        "facebook": None,
        "twitter": None,
        "description": None,
    }

    if not url:
        return empty

    if not url.startswith("http"):
        url = "https://" + url

    try:
        response = requests.get(url, headers=HEADERS, timeout=10, allow_redirects=True)
        response.raise_for_status()
        html = response.text
    except Exception:
        return empty

    try:
        soup = BeautifulSoup(html, "lxml")
    except Exception:
        try:
            soup = BeautifulSoup(html, "html.parser")
        except Exception:
            return empty

    emails: set = set()

    # From raw HTML text
    emails.update(_extract_emails_from_text(html))

    # From mailto: links
    for a in soup.find_all("a", href=True):
        href = a["href"]
        if href.startswith("mailto:"):
            addr = href[7:].split("?")[0].strip()
            if addr:
                emails.add(addr)

    cleaned_emails = _clean_emails(emails)
    social = _extract_social_links(soup)
    description = _extract_description(soup)

    return {
        "emails": cleaned_emails,
        **social,
        "description": description,
    }
