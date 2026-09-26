# Worker Adapter

This folder is intentionally a production scaffold.

Suggested API contract:

POST /jobs
{
  "type": "ai_influencer",
  "template_id": "...",
  "input_image": "...",
  "character": {...},
  "voice": {...}
}

Worker stages:
1. validate input
2. create prompt
3. generate image
4. generate voice if requested
5. run talking-video model if requested
6. upload artifacts to R2
7. report status back to MagicAI

Keep model execution outside the Laravel request lifecycle.
