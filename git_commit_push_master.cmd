git config --local core.autocrlf false
git add *
git commit -a --allow-empty-message -m ''
git push origin
:: http://stackoverflow.com/questions/424071/how-to-list-all-the-files-in-a-commit
git log --pretty="format:" --stat -1
@pause