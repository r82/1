@cd "%~dp0"

@git > null
:: http://stackoverflow.com/a/2541820
@IF [%ERRORLEVEL%] NEQ [1] GOTO :NOT_GIT

@set user.email=
@for /F "usebackq delims=" %%X IN (` git config --get user.email `) DO @(
  set user.email="%%X"
)
@IF [%user.email%] EQU [""] @(
  set /P user.email=user.email ?: 
)
@IF [%user.email%] NEQ [""] @(
  git config user.email %user.email%
)
@echo user.email: %user.email%

@set user.name=
@for /F "usebackq delims=" %%X IN (` git config --get user.name `) DO @(
  set user.name=%%X
)
@IF [%user.name%] EQU [] @(
  set /P user.name=user.name ?: 
)
@IF [%user.name%] NEQ [] @(
  git config user.name %user.name%
)
@echo user.name: %user.name%

:: http://stackoverflow.com/questions/1967370/git-replacing-lf-with-crlf
git config --local core.autocrlf false
git add -A
git status
git commit -a --allow-empty-message -m ''
git push origin

:: http://stackoverflow.com/questions/424071/how-to-list-all-the-files-in-a-commit
git log --pretty="format:" --stat -1
@pause
@exit

:NOT_GIT

@set git_cmd_path=
@IF EXIST "../GitPortable/git-cmd.exe" @(
  set git_cmd_path="../GitPortable/git-cmd.exe"
)
@echo %git_cmd_path%

:: http://stackoverflow.com/a/2541820
@IF [%git_cmd_path%] NEQ [] @(
  start "" /b /belownormal /wait %git_cmd_path% "%~dpnx0"
)