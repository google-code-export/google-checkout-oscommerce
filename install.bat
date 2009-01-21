@setlocal 

REM This script wraps the python install script

@echo off
echo Running the installer

set target_dir=%1
set installer=python tools\installer.py
REM set installer=tools\windows\installer.exe

if defined target_dir (
  %installer% --diff3=tools\windows\diffutils\bin\diff3.exe catalog\ oscommerce-2.2rc2a\catalog\ %target_dir%\
) else (
  echo You need to supply the directory to install to.
)
@endlocal

