<div style="padding:12px 16px;border-left:4px solid #d29922;background:#fff8e1;">
  <strong>⚠️ This is the Harmonist Hub Public version for portability testing and not for final release.</strong>
</div>

# IeDEA-Harmonist Build & Deploy Steps

Before installing the Harmonist Hub External Module, please ensure that you have the following modules downloaded as well:

1. Email Alerts External Module
2. Data Model Browser External Module
3. Get PMID External Module

These modules are dependencies for the Harmonist Hub and will be automatically activated in the projects when you install the Hub.

## 1. Recover Project Data through SSH

To recover the project's data through SSH, you first have to go to GitHub and download the project, which is under the name *harmonist-hub-public*.

1. Go to GitHub: [https://github.com/vanderbilt-redcap/harmonist-hub-public](https://github.com/vanderbilt-redcap/harmonist-hub-public)
2. Click on the **<> Code** button and copy the SSH URL.
   ![GitHub's Harmonist main page](/docs/images/image1.png)
3. On the server, in the modules folder, do **git clone \<url\> harmonist-hub-public_v0.0.0** to copy the project.
4. For Harmonist Hub, the name of the folder has to be `harmonist-hub-public_v0.0.0`. If you choose a different name, you will have to change the name before installing the data.
5. Inside the `harmonist-hub-public_v0.0.0` directory, run `composer install` to install the dependencies.
6. You will also have to download the code for:
   1. [Email Alerts External Module](https://github.com/vanderbilt-redcap/email-alerts-module)
      - For Email Alerts, the name of the folder has to be `vanderbilt_emailTrigger_v0.0.0`
   2. [Data Model Browser External Module](https://github.com/vanderbilt-redcap/data-model-browser)
      - For Data Model Browser, the name of the folder has to be `data-model-browser_v0.0.0`
   3. [Get PMID External Module](https://github.com/vanderbilt-redcap/get-pmid-details)
      - For Get PMID Details, the name of the folder has to be `get-pmid-details_v0.0.0`
7. After downloading the code, you will have to run `composer install` on the Data Model Browser External Module to install its dependencies.

## 2. Activate and Configure the Harmonist Module on a Project

Once the project that will host Harmonist Hub is created in REDCap, we need to activate the module.

1. First, an administrator has to activate the module on the Control Center if it has not been activated previously.
   1. Control Center → External Modules → Manage → Enable a module → Enable
   2. This has to be done for all modules mentioned in the beginning of this document, if they have not been activated previously.
2. Afterwards, activate the module on the project.
   1. External Modules → Manage → Enable a Module
3. Once the module is active, click on Configure.

![External Modules Manager's Page](/docs/images/image2.png)
![Harmonist's External Modules Manager Configuration Page](/docs/images/image3.png)

4. In the configuration window, enter the mandatory fields.
   1. **Hub Name**: The name the hub and projects will have. This will be the main name added to all projects. It is recommended not to use a very long name.
   2. **Hub Profile**: This field is mandatory but any value works.

## 3. First-Time Install

If this is the first time activating the module, after configuring it, you need to install all the projects that will make the module work.

1. On the External Modules section, click on the new link that will appear. The first time you see the link, it will be under Harmonist Hub; this will change later to your Hub's name.

![Harmonist's External Module Link](/docs/images/image4.png)

2. A message will display prompting the user to click the button to create and install the necessary projects to use the external module.

![Harmonist's first-time installation notice](/docs/images/image5.png)

3. Click the button to install the projects, and you are ready to start adding data to them.
4. It is recommended to start with the "Settings" project.

## 4. Copying Data

When copying data from another project, simply export the data from the project and import it to the new project.
Keep in mind that images, like logos on the Settings project, PDFs, JSON, and other files will not be copied and will have to be added manually.
Also, some existing projects might have some variables that do not exist in the original installation, as Hubs can add new variables as needed. You will have to manually add those variables if you want an exact copy of the project.