# ManyMailerPlus

## Welcome to ManyMailerPlus

This ExpressionEngine extension/module allows you to send custom emails (using placeholders) to send emails to a list of people using a csv list!
The email for is pretty much the same as the native 'Communicate' utility with a new enhancement of the 'Recipent Entry Method':

### Installation

Move the 'manymailerplus' folder to the *./system/user/addons* directory

### Usage

#### Entry Methods

##### Default

Default refers to typing in the 'Primary Recipients' textbox

![Method of Entry](./images/recip_method.png)

##### CSV (Raw)

This method accepts a pasted CSV file.

![CSV Paste](./images/recip2.png)

##### CSV (Upload)

Enables uploads of local file

![CSV Paste](./images/recip3.png)

### Required Columns

#### Email Column

- Column title is some form of the following string(email, mail, e-mail, address)

#### First Name Column

- Column title is some form of the following string(first, given, forename)

#### Last Name Column

- Column title is some form of the following string(last, surname)

### Optional Columns

All other columns will be automatically injested to create tokenized placeholder buttons for use during email composition.

## Changelog

### [1.0.0] - 2019-03-22 - ManyMailerPlus v1.0

- commited 20 files [(98e948b)](https://github.com/StolenThunda/manymailerplus/commit/98e948bc35c1678b03a0e3e970e5cca6b681f693)
- converted extension to module
- added new view for module
- fixed bug in debug_helper to refer to the last class it can find to output

### [0.1.5] - 2019-03-14 - Beta release

Current Release has the ability to:

- send emails
- view sent mail
- separate template/actual emails in log
