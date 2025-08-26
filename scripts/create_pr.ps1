# Safe PowerShell script to create a feature branch, commit, and push changes.
# Usage: Run from repo root in PowerShell after reviewing changes.

param(
    [string]$branch = "feature/role-filters-fix",
    [string]$commitMessage = "feat(filtration): align dashboard tasks with role_filters"
)

Write-Host "This script will create branch: $branch and commit changes."
Write-Host "Please ensure you have no uncommitted work you want to keep elsewhere."

# Create branch
git checkout -b $branch

# Stage changes
git add .

# Commit
git commit -m $commitMessage

# Push
git push -u origin $branch

Write-Host "Branch created and pushed. Create a PR on GitHub from branch $branch to main."
