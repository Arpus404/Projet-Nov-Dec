@echo off
cd /d "%~dp0"

rem Cherche le php.exe de WAMP (marche meme si la version change d'un PC a l'autre)
set PHP=
for /d %%D in (C:\wamp64\bin\php\php*) do set "PHP=%%D\php.exe"
if not defined PHP (
    echo PHP de WAMP introuvable dans C:\wamp64\bin\php
    pause
    exit /b
)

rem Accents corrects dans la console
chcp 65001 > nul

rem Detection automatique de l'ESP. Pour imposer un port : ajoute COM3 a la fin de la ligne
"%PHP%" ecoute_serie.php
pause
