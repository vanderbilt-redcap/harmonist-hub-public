# IeDEA-Harmonist Bootstrap styles modifications


## How to generate the styles

The Harmonist Hub now utilizes SCSS styles. To begin modifying the CSS, follow the steps below:

### Steps to Modify SCSS:

1. **Install NPM globally**  
   Ensure that Node.js and npm are installed on your system.

2. **Install the SCSS compiler globally**  
   Run the following command:
   ```bash
   npm install -g sass

3. **In project npm install bootstrap**
   Inside your project directory, run:
   ```bash
   npm install bootstrap

4. **Add `/node_modules` to `.gitignore`**  
   Prevent the `node_modules` folder from being committed to your repository by adding it to your `.gitignore` file.

5. **Commit the `package.json` file**  
   After installing dependencies, commit the `package.json` file to your branch to ensure consistency.

6. **Create an `scss` directory**  
   Inside your project folder, create a new directory named `scss`.

7. **Create a `custom.scss` file**  
   Inside the `scss` directory, create a file named `custom.scss`. This file will contain all your custom styles.

   Example content for `custom.scss`:
   ```scss
   $primary: #78b7fc;
   $danger: #ff6539;

   @import "../node_modules/bootstrap/scss/bootstrap";

8. **Compile the SCSS to CSS**  
   Run the following command to compile your SCSS file into a CSS file:
   ```bash
   sass scss/custom.scss css/custom.css
9. **Compile a minified version for production**  
   Generate a compressed CSS file for production by running:
   ```bash
   sass scss/custom.scss css/custom.min.css --style=compressed
   