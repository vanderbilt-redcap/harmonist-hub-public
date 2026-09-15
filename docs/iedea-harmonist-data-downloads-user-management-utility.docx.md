# IeDEA-Harmonist Data Downloads User Management Utility

## Accessing the Data Downloads User Management Utility

You have two options to access the **Data Downloads User Management Utility**:

1. On your **Hub: Parent Project (MAP)**, navigate to the left column under **External Modules**, and click on the link **Data Downloads User Management**.  
   <img src="/docs/images/data-download1.png" alt="Screenshot of the Data Downloads User Management Utility link">

2. On your **Hub: People (5)** project, navigate to the left column under **External Modules**, and click on the link **Data Downloads User Management**.  
   <img src="/docs/images/data-download2.png" alt="Screenshot of the Data Downloads User Management Utility link">

After clicking the link, the main page of the utility should load, as shown below:  
<img src="/docs/images/data-download3.png" alt="Screenshot of the Data Downloads User Management Utility main page">

---

## Resolving Permission Conflicts

If there are users who are **missing the correct permissions**, a notification message will appear. To address these issues, click the **Resolve Permissions Conflicts** button.

<img src="/docs/images/data-download4.png" alt="Screenshot of the Resolve Permissions Conflicts message">

A list of users with **missing permissions** will be displayed.

<img src="/docs/images/data-download5.png" alt="Screenshot of the Resolve Permissions Conflicts page">

On this page, you can:
- Add or remove users.
- Search for users by name or text.
- Filter by Admins.

Each row will indicate the specific issue with a user. You can collapse this information for a cleaner view. To view a user's REDCap record, click on **View Record**, and a new tab will open with the record details.

### Adding a User

To add a user to the Hub Data Downloads feature, follow these steps:

1. Select the user and click on **Add User**.

There are two possible scenarios:

- **Scenario 1: The user does not exist in REDCap**  
  If the user does not already exist in REDCap, you will need to provide their username. A search input will appear, allowing you to look up the username.  
  <img src="/docs/images/data-download6.png" alt="Screenshot of the Add/Repair Data Downloads modal">

- **Scenario 2: The user exists but lacks permissions**  
  If the user already exists in REDCap but is missing permissions, their information will be displayed. Simply confirm the action to proceed.  
  <img src="/docs/images/data-download8.png" alt="Screenshot of the Resolve Permissions Conflicts page">

2. Click on **Add** to finalize the process. The utility will automatically assign the required permissions, and a confirmation message will appear.  
   <img src="/docs/images/data-download9.png" alt="Screenshot of the Add/Repair Data Downloads confirmation modal">

After successfully adding the user, they will appear on the main **Data Downloads User Management** page unless there are additional issues that need resolution.

### Removing a User

To remove a user:

1. Select the user you want to remove.
2. Click on **Remove User**.

<img src="/docs/images/data-download7.png" alt="Screenshot of the Remove User modal">

A confirmation message will appear to ensure you want to proceed.  
<img src="/docs/images/data-download11.png" alt="Screenshot of the Remove User confirmation message">

---

## Data Downloads User Management Overview

The **Data Downloads User Management** page displays all users who have the **correct permissions** to access the Hub Data Downloads feature.

On this page, you can:
- Remove users.
- Search for users by name or text.
- Filter by Admins.

To view a specific user's REDCap record, click on **View Record**. This will open the record in a new tab.

<img src="/docs/images/data-download3.png" alt="Screenshot of the Data Downloads User Management Utility main page">

### Removing a User

To remove a user from the system:

1. Select the user you wish to remove.
2. Click on **Remove User**.

<img src="/docs/images/data-download7.png" alt="Screenshot of the Remove User modal">

A confirmation message will appear to verify your action.  
<img src="/docs/images/data-download11.png" alt="Screenshot of the Remove User confirmation message">