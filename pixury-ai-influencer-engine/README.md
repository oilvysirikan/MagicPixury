# Pixury AI Influencer Engine — Extracted Reference

This package isolates the AI-Influencer-Generator's reusable pipeline concepts for integration
into a production Pixury/MagicAI architecture.

## Pipeline

Character / Product description
→ LLM prompt generation
→ image generation
→ optional voice generation
→ talking-video generation
→ output media

## Original project components observed

- Google Colab / Jupyter Notebook
- OpenAI GPT-4o-mini prompt generation
- Stable Diffusion 2.1 image generation
- gTTS voice generation
- SadTalker talking-head video
- GFPGAN face enhancement
- PyTorch / CUDA

## Production adaptation

Do NOT run the notebook as the production web request handler.

Recommended architecture:

MagicAI / Laravel
→ queue/job
→ AI Worker
→ image / voice / video stage
→ R2
→ generation record

The files in this package are an extracted reference implementation/adapter scaffold.
Original MIT license notice is retained in LICENSE.
