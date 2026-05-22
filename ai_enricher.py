import os

import anthropic

_SYSTEM_PROMPT = """\
You are an expert cold-email copywriter specializing in hyper-personalized outreach.

Given information about a business scraped from Google Maps and their website, write a \
single personalized opening sentence (1-2 sentences MAX) for a cold email that:
- References something SPECIFIC and UNIQUE about this business \
(their specialty, star rating, years in business, awards, location prominence, signature service, niche focus, etc.)
- Sounds like genuine research, not a template
- Is warm and professional
- Does NOT start with "I" or generic openers like "I hope this email finds you well"
- Does NOT mention that their website was visited or data was scraped
- Is 1-2 sentences only

Examples:
"As one of Istanbul's most celebrated 5-star Bosphorus-view hotels, your reputation for blending Ottoman heritage with modern luxury clearly sets you apart from the competition."
"Your boutique real estate firm's decade-long focus on Nişantaşı and Bebek has earned you a distinctive name in Istanbul's premium property market."
"With 25 years of dental excellence in Kadıköy and six in-house specialists, your clinic has clearly become the go-to destination for comprehensive care on the Asian side."

Return ONLY the personalization text — no quotes, no explanation, nothing else.\
"""

_CLIENT: anthropic.Anthropic | None = None


def _get_client() -> anthropic.Anthropic | None:
    global _CLIENT
    if _CLIENT is not None:
        return _CLIENT
    api_key = os.environ.get("ANTHROPIC_API_KEY")
    if not api_key:
        return None
    _CLIENT = anthropic.Anthropic(api_key=api_key)
    return _CLIENT


def generate_personalization(
    business_name: str,
    search_query: str,
    location: str,
    description: str | None,
    page_text: str | None,
    rating: float | None,
    reviews: int | None,
) -> str | None:
    client = _get_client()
    if client is None:
        return None

    parts = [f"Business name: {business_name}"]
    parts.append(f"Search category / type: {search_query}")
    parts.append(f"Location: {location}")
    if rating is not None:
        parts.append(f"Google Maps rating: {rating}/5 stars")
    if reviews is not None:
        parts.append(f"Number of Google reviews: {reviews}")
    if description:
        parts.append(f"Website description: {description}")
    if page_text:
        parts.append(f"Website content excerpt:\n{page_text[:2500]}")

    user_content = "\n".join(parts)

    try:
        response = client.messages.create(
            model="claude-opus-4-7",
            max_tokens=300,
            system=[
                {
                    "type": "text",
                    "text": _SYSTEM_PROMPT,
                    "cache_control": {"type": "ephemeral"},
                }
            ],
            messages=[{"role": "user", "content": user_content}],
        )
        block = next((b for b in response.content if b.type == "text"), None)
        return block.text.strip() if block else None
    except Exception:
        return None
