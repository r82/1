@cd "%~dp0"

git > nul
:: http://stackoverflow.com/a/2541820
IF [%ERRORLEVEL%] NEQ [1] GOTO :NOT_GIT

:: http://stackoverflow.com/questions/1967370/git-replacing-lf-with-crlf
git config --local core.autocrlf false

:: https://stackoverflow.com/questions/2411031/how-do-i-clone-into-a-non-empty-directory
:: git init
git remote add origin https://github.com/r82/1
git fetch
git checkout -t origin/master
::mv temp/.git code/.git
::rm -rf temp
pause
@exit

:NOT_GIT

set git_cmd_path=
IF EXIST "../GitPortable/git-cmd.exe" (
  set git_cmd_path="../GitPortable/git-cmd.exe"
)
echo %git_cmd_path%

:: http://stackoverflow.com/a/2541820
IF [%git_cmd_path%] NEQ [] (
  start "" /b /belownormal /wait %git_cmd_path% "%~dpnx0"
)