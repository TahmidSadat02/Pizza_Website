# Git Deployment Guide

## For Future Updates

When you make changes to your pizza delivery website, follow these steps to push updates to GitHub:

### 1. Check Status
```bash
cd /Applications/XAMPP/xamppfiles/htdocs/PizzaWebsite
git status
```

### 2. Add Changes
```bash
# Add all changes
git add .

# Or add specific files
git add pizza_delivery/public/index.php
```

### 3. Commit Changes
```bash
git commit -m "Your descriptive commit message here"
```

### 4. Push to GitHub
```bash
git push origin main
```

## Common Git Commands

### Check what's changed
```bash
git diff
```

### View commit history
```bash
git log --oneline
```

### Create a new branch for features
```bash
git checkout -b new-feature-name
```

### Switch back to main branch
```bash
git checkout main
```

### Pull latest changes from GitHub
```bash
git pull origin main
```

## Best Practices

1. **Commit Often** - Make small, frequent commits with clear messages
2. **Use Branches** - Create feature branches for new functionality
3. **Test Before Push** - Always test your changes locally first
4. **Clear Messages** - Write descriptive commit messages
5. **Backup** - Keep your local files backed up

## Commit Message Examples

- `"Fix: Resolve login form validation issue"`
- `"Feature: Add new payment gateway integration"`
- `"Update: Improve mobile responsive design"`
- `"Fix: Correct image upload path issue"`
- `"Style: Update color scheme for better contrast"`

## Repository Information

- **Repository**: https://github.com/TahmidSadat02/Pizza_Website.git
- **Main Branch**: main
- **Local Path**: /Applications/XAMPP/xamppfiles/htdocs/PizzaWebsite
