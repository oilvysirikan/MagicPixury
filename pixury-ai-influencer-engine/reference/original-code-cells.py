# Extracted Notebook Code Reference
# This is a reference extraction from the original notebook; inspect and adapt before production use.


# ===== ORIGINAL CODE CELL 1 =====
### make sure that CUDA is available in Edit -> Nootbook settings -> GPU
!nvidia-smi --query-gpu=name,memory.total,memory.free --format=csv,noheader

# ===== ORIGINAL CODE CELL 2 =====
### Dependency installations
!update-alternatives --install /usr/local/bin/python3 python3 /usr/bin/python3.8 2
!update-alternatives --install /usr/local/bin/python3 python3 /usr/bin/python3.9 1
!sudo apt install python3.8

!sudo apt-get install python3.8-distutils

!python --version

!apt-get update

!apt install software-properties-common

!sudo dpkg --remove --force-remove-reinstreq python3-pip python3-setuptools python3-wheel

!apt-get install python3-pip

print('Git clone project and install requirements...')
!git clone https://github.com/Winfredy/SadTalker &> /dev/null
%cd SadTalker
!export PYTHONPATH=/content/SadTalker:$PYTHONPATH
!python3.8 -m pip install torch==1.12.1+cu113 torchvision==0.13.1+cu113 torchaudio==0.12.1 --extra-index-url https://download.pytorch.org/whl/cu113
!apt update
!apt install ffmpeg &> /dev/null
!python3.8 -m pip install -r requirements.txt
!pip install openai
!pip install gtts
!pip install diffusers accelerate transformers
!pip install requests

# ===== ORIGINAL CODE CELL 3 =====
### Models used for lisyncing 
print('Download pre-trained models...')
!rm -rf checkpoints
!bash scripts/download_models.sh

# ===== ORIGINAL CODE CELL 5 =====
import requests
import uuid
import time
from openai import OpenAI
import json
from gtts import gTTS
import os

# ============================================
# IMPORTANT: Add your OpenAI API key here
# Get your key from: https://platform.openai.com/api-keys
# ============================================
# Option 1: Use Colab Secrets (Recommended)
# Go to the key icon in the left sidebar, add a secret named "OPENAI_API_KEY"
try:
    from google.colab import userdata
    OPENAI_API_KEY = userdata.get('OPENAI_API_KEY')
except:
    OPENAI_API_KEY = None

# Option 2: Paste your key directly (less secure)
if not OPENAI_API_KEY:
    OPENAI_API_KEY = "your-openai-api-key-here"  # Replace with your actual key

if OPENAI_API_KEY == "your-openai-api-key-here" or not OPENAI_API_KEY:
    raise ValueError("Please add your OpenAI API key! Either use Colab Secrets or replace the placeholder above.")

client = OpenAI(api_key=OPENAI_API_KEY)
print("✓ OpenAI client initialized successfully!")

def get_prompt_for_image(characterstics):
    """Function to get the prompt for an Image.

    parameters:
    characterstics (str): Characterstics of the person for the image.

    return:
    str: Prompt for the image of the person.
    """

    prompt = f"""Given below are some characterstics of a person for a single scene in a video, give output a prompt for image generating model to generate a image of the person based on the given characerstics.

    Below are some guidelines for the image:
    A realistic human face, taken from the front.The person should look directly at the camera.Maintain a relaxed expression with a neutral or natural smile.Center the head and shoulders in the frame.Use a simple background with natural or soft lighting.Avoid the following: Cartoonish or non-human elements.Facing the camera sideways or positioning too close/far.Overexposed, underexposed, or harsh shadows.Moving objects such as animals or people in the background.

    Strictly give output in json format as below

    Output:
    {{"prompt": output_prompt}}

    Characterstics:
    {characterstics}

"""

    chat_completion = client.chat.completions.create(
        messages=[
            {
                "role": "user",
                "content": prompt,
            }
        ],
        model="gpt-4o-mini",
        temperature=0.5,
    )
    data = chat_completion.choices[0].message.content
    try:
        data = json.loads(data)
    except Exception as e:
        print("Parsing JSON response...", e)
        data = data.split('```json')[1].split('```')[0].replace('\n', '')
        data = json.loads(data)

    return data["prompt"]

# ===== ORIGINAL CODE CELL 6 =====
from diffusers import DiffusionPipeline

def generate_avatar_image(image_prompt):
  """Function to generate avatar image

  parameters:
  image_prompt (str): prompt to generate avatar image

  returns:
  image_path (str): path to the generated avatar image
  """
  
  pipe = DiffusionPipeline.from_pretrained("stabilityai/stable-diffusion-2-1").to("cuda")
  image = pipe(image_prompt).images[0]
  print(image)
  image_path = "examples/source_image/generated_image.png"
  image.save(image_path)

  return image_path

# ===== ORIGINAL CODE CELL 7 =====
from gtts import gTTS
import os

def generate_voiceover(text, filename):
    """Function to generate the voiceover using gTTS

    parameters:
    text (str): the script text for which voiceover needs to be generated
    filename (str): name of the file with which voiceover will be saved

    """

    tts = gTTS(text)
    tts.save("temp.mp3")
    os.system(f"ffmpeg -i temp.mp3 -ar 16000 -ac 1 {filename}")
    os.remove("temp.mp3")

# ===== ORIGINAL CODE CELL 8 =====
# selected audio from exmaple/driven_audio
from IPython.display import HTML
from base64 import b64encode
import os, sys
import glob
def create_ai_influencer(image_path,audio_path):
  """Function to create ai influencer video using image and voiceover

  parameters:
  image_path (str): path to the image file generated
  audio_path (str): path to the audio file generated

  returns:
  results (list): list of geenrated files in the directory
  """
  !python3.8 inference.py --driven_audio {audio_path} --source_image {image_path} --result_dir ./results --still --preprocess full --enhancer gfpgan

  results = sorted(os.listdir('./results/'))
  return results

# ===== ORIGINAL CODE CELL 9 =====
def init(avatar_details, script):

    """Function to generate ai influencer videos by first generating the avatar image using
    the prompt and the characterstics and then generating the voiceover using script.
    With these generated image and voiceover, AI influencer video is generated.

    parameters:
    avatar_details (dict) : characterstics of the avatar
    script (str) : script to generate the voiceover

    returns:
    res (list) : list of the generated files in the folder
    """
    characterstics = avatar_details.get('characterstics')
    prompt = get_prompt_for_image(characterstics)
    print("Image prompt: " + prompt)
    avatar_image_url = generate_avatar_image(prompt)
    print("avatar_image_url: " + avatar_image_url)
    voice_path = "examples/driven_audio/audio.wav"
    generate_voiceover(script,voice_path)

    res = create_ai_influencer(avatar_image_url,voice_path)
    print(res)


### Init function to generate the Influencer video
init({'characterstics':'male European, wearing glasses and leather jacket with a smile on face'}, "Our planet is a masterpiece of life, a delicate balance of ecosystems. But this harmony is under threat.Every year, 8 million tons of plastic enter our oceans, choking marine life. Forests, the lungs of our planet, are disappearing at an alarming rate. Climate change fuels disasters, from rising seas to devastating wildfires.But there is hope. Across the globe, people are rising to the challenge. Simple actions—reducing waste, conserving energy, and protecting wildlife—can make a difference.The future of our planet is in our hands. Together, we can turn the tide and protect our only home. Act now, because every choice matters.")
