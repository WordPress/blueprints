## WordPress Playground default "Welcome" Blueprint

This folder contains the default Blueprint applied when visiting playground.wordpress.net with no parameters:

<img width="500" alt="CleanShot 2025-10-07 at 18 27 09@2x" src="https://github.com/user-attachments/assets/c27c39b3-36d5-4d7f-94be-34c3e9222a04" />


This Blueprint helps newcomers understand what Playground is without altering how WordPress is normally set up. The only change it applies is inserting one extra page with onboarding instructions. There are no custom menus, global styles, mu-plugins, etc. 

When the default instance looks and behaves like WordPress core, it remains a reliable baseline for everyone. New users get useful instructions, and experienced Playground developers get the vanilla WordPress experience they need.

### Working with the welcome Blueprint

Updating the content is cumbersome since we intentionally avoid the typical tools such as a WXR import plugin, custom themes, The content of the sole page this Blueprint inserts is shipped as a JSON-encoded string inside the blueprint.json. Here's how you can update it:

	1.	Run the Blueprint inside Playground.
	2.	Edit the welcome page in WordPress (e.g., update text or layout).
	3.	Switch to the code editor and copy the HTML.
	4.	Convert that code into a JSON string.
	5.	Replace the existing content in the Blueprint file.

