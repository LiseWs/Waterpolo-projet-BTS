@echo off
cd /d "%~dp0"
set PYTHONPATH=%~dp0Django
python-runtime\python.exe Django\manage.py migrate --run-syncdb
