@echo off
cd /d "%~dp0"
python-runtime\python.exe -c "import sys,os; sys.path.insert(0,os.path.join(os.getcwd(),'Django')); from django.core.management import execute_from_command_line; execute_from_command_line(['manage.py','migrate','--run-syncdb'])"
