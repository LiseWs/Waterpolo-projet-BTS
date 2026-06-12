import sys
import os

base = os.path.dirname(os.path.abspath(__file__))
sys.path.insert(0, os.path.join(base, 'Django'))

from waitress import serve
from waterpolo_site.wsgi import application

serve(application, host='127.0.0.1', port=8000, threads=8)
