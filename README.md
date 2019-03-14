
# ManyMailerPlus

## Welcome to ManyMailerPlus

This extension allows you to send custom emails (using placeholders) to send emails to a list of people using a csv list!
The email for is pretty much the same as the native 'Communicate' utility with a new enhancement of the 'Recipent Entry Method':


### Installation 

Move the 'manymailerplus' folder to the *./system/user/addons* directory

### Usage

#### Default entry method

Default refers to typing in the 'Primary Recipients' textbox

![Method of Entry](./images/recip_method.png)

#### CSV entry method

This method accepts a pasted CSV file.

![CSV Paste](./images/recip2.png)

#### CSV Upload method

Enables uploads of local file

![CSV Paste](./images/recip3.png)

### Required Columns

- Email Column
  - column title is some form of the following string(email, mail, e-mail, address)
- First Name Column:
  - column title is some form of the following string(first, given, forename)
- Last Name Column:
  - column title is some form of the following string(last, surname)

### Optional Columns

All other columns will be automatically injested to create tokenized placeholder buttons for use during email composition.