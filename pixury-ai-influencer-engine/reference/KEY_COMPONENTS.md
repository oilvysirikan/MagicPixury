# Key references found in extracted notebook

## DiffusionPipeline
111: from diffusers import DiffusionPipeline
123: pipe = DiffusionPipeline.from_pretrained("stabilityai/stable-diffusion-2-1").to("cuda")

## gTTS
29: !pip install gtts
41: from gtts import gTTS
130: from gtts import gTTS
134: """Function to generate the voiceover using gTTS
142: tts = gTTS(text)

## SadTalker
21: !git clone https://github.com/Winfredy/SadTalker &> /dev/null
22: %cd SadTalker
23: !export PYTHONPATH=/content/SadTalker:$PYTHONPATH

## GFPGAN
163: !python3.8 inference.py --driven_audio {audio_path} --source_image {image_path} --result_dir ./results --still --preprocess full --enhancer gfpgan

## OpenAI
28: !pip install openai
39: from openai import OpenAI
45: # IMPORTANT: Add your OpenAI API key here
46: # Get your key from: https://platform.openai.com/api-keys
49: # Go to the key icon in the left sidebar, add a secret named "OPENAI_API_KEY"
52: OPENAI_API_KEY = userdata.get('OPENAI_API_KEY')
54: OPENAI_API_KEY = None
57: if not OPENAI_API_KEY:
58: OPENAI_API_KEY = "your-openai-api-key-here"  # Replace with your actual key
60: if OPENAI_API_KEY == "your-openai-api-key-here" or not OPENAI_API_KEY:
61: raise ValueError("Please add your OpenAI API key! Either use Colab Secrets or replace the placeholder above.")
63: client = OpenAI(api_key=OPENAI_API_KEY)
64: print("✓ OpenAI client initialized successfully!")

## openai
28: !pip install openai
39: from openai import OpenAI
45: # IMPORTANT: Add your OpenAI API key here
46: # Get your key from: https://platform.openai.com/api-keys
49: # Go to the key icon in the left sidebar, add a secret named "OPENAI_API_KEY"
52: OPENAI_API_KEY = userdata.get('OPENAI_API_KEY')
54: OPENAI_API_KEY = None
57: if not OPENAI_API_KEY:
58: OPENAI_API_KEY = "your-openai-api-key-here"  # Replace with your actual key
60: if OPENAI_API_KEY == "your-openai-api-key-here" or not OPENAI_API_KEY:
61: raise ValueError("Please add your OpenAI API key! Either use Colab Secrets or replace the placeholder above.")
63: client = OpenAI(api_key=OPENAI_API_KEY)
64: print("✓ OpenAI client initialized successfully!")

## inference.py
163: !python3.8 inference.py --driven_audio {audio_path} --source_image {image_path} --result_dir ./results --still --preprocess full --enhancer gfpgan
