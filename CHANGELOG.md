# ManyMailerPlus

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/) and this project adheres to [Semantic Versioning](https://semver.org/).

## ChangeLog

---

## [0.1.1] - 2019-02-21 ***`committed (b4e8596) 14 changed files`***

### Milestone

- Able to view cached emails
- Maintaining lang file updates

### Todo

- [ ] fix email management funcs

### Added

- [x] styling for placeholder on compose page

### Changed

- config\sidebar.php
  - temporarily disabled services functions 

### Removed

- View files:
  - csv-field.php (deprecated)
  - sidebar_view.php (deprecated)

### Fixed

- [x] Double success bug
  - ![Double Message Fix](./images/double_message_fix.png)

---

## [v0.1.0] - 2019-02-20 ***`committed (5b537c7) 18 changed files`***

### Milestones

- [x] emails successfully
- [x] upload csv
- [x] paste csv
- [x] parse emails from csv
- [x] creates placeholder buttons
- [x] sends email replacing placeholders

### Todo (functionality)

- [ ] Get working sent page with resend functionality
- [ ] update lang file

  ***Wishlist***
  
  - [ ] better styling
  - [ ] better sidebar (jquery)
  - [ ] handle mobile

### Bugs

- double message on compose page

  - ![Double Message](./images/double_messages.png)