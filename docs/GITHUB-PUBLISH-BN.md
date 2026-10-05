# GitHub থেকে Rejoyan প্লাগিন WordPress.org-এ প্রকাশ

আপনার দেওয়া approval email অনুযায়ী username: rejoyan9009
SVN URL: https://plugins.svn.wordpress.org/rejoyan-crm-campaigns-for-woocommerce
Public URL: https://wordpress.org/plugins/rejoyan-crm-campaigns-for-woocommerce/
ইমেইলে বলা হয়েছে approval-এর এক ঘণ্টার মধ্যে commit access চালু হবে; সেই সময় পার হওয়ার আগে deployment authentication ব্যর্থ হতে পারে।

এই GitHub automation-এর জন্য এই নির্দেশিকা অনুসরণ করুন। পুরোনো UPLOAD-GUIDE-BN.md-এর manual SVN ধাপ এখানে লাগবে না; release-এর tag নাম এই workflow-তে 1.0.0 হবে।

## ১ — এই নতুন ZIP ব্যবহার করুন

rejoyan-github-publish-ready-1.0.0.zip Extract করুন। ভিতরের rejoyan-crm-campaigns-for-woocommerce ফোল্ডার খুলুন। মূল PHP ফাইল সরাসরি GitHub repository root-এ যাবে। আগের wordpress-svn-layout ZIP GitHub repository-তে আপলোড করবেন না। এখানে workflow নিজেই SVN-এর trunk/tag সাজাবে।

## ২ — GitHub repository তৈরি ও সোর্স upload

https://github.com/new → Repository name: rejoyan-crm-campaigns-for-woocommerce → Public → Create repository। README/license স্বয়ংক্রিয়ভাবে তৈরি না করলেও চলে, কারণ প্যাকেজে আছে।
খালি repository-তে uploading an existing file; বিদ্যমান repository-তে Add file → Upload files। Extract করা plugin ফোল্ডারের **ভিতরের** ফাইল এবং includes/, assets/, docs/ upload করুন। Commit changes।
Chrome Android হলে ⋮ → Desktop site চালু করুন। ফোনের ফাইল নির্বাচক subfolder আপলোড করতে না পারলে কম্পিউটারে drag-and-drop ব্যবহার করুন; includes বা assets ছাড়া প্রকাশ করবেন না।

## ৩ — workflow ও hidden ফাইল নিশ্চিত করুন

কিছু ফোনে .github ও .distignore দেখা যায় না। repository-তে এগুলো অবশ্যই থাকতে হবে। প্রয়োজনে ওয়েবসাইট থেকে তৈরি করুন:

- Add file → Create new file
- filename: .github/workflows/deploy.yml
- আলাদা দেওয়া rejoyan-deploy.yml খুলে পুরো লেখা copy/paste করুন → Commit changes।
- আবার Create new file; filename: .distignore
- নিচের আট লাইন দিন → Commit changes:

```
/.git
/.github
/.wordpress-org
/docs
/README.md
/.gitignore
/.gitattributes
/.distignore
```

Workflow শুধু non-prerelease প্রকাশ করলে চলে। upload/commit বা draft save করলেই WordPress.org-এ পাঠায় না। এই workflow-তে GitHub tag 1.0.0 হবে, v1.0.0 নয়। 10up-এর deployment action SVN-এ trunk ও tags/1.0.0 তৈরি করে। README/docs/workflow মূল plugin release থেকে বাদ যাবে।

## ৪ — WordPress.org SVN password তৈরি

এই লিংক খুলুন:
https://profiles.wordpress.org/rejoyan9009/profile/edit/group/3/?screen=svn-password

WordPress.org-এ rejoyan9009 হিসেবে লগইন করুন। Account & Security → SVN credentials → Generate Password → Copy। প্রয়োজন হলে WordPress-এর নির্দেশ অনুসারে Two-Factor Authentication setup করুন। এটি আলাদা SVN password; WordPress login password নয়। Password শুধু নিচের GitHub Secret-এ paste করুন, public ফাইল/README/চ্যাটে নয়।

## ৫ — GitHub-এ দুই Secret বসান

আপনার repository খুলুন → Settings → Secrets and variables → Actions → New repository secret।

| Name | Secret value |
|---|---|
| SVN_USERNAME | rejoyan9009 |
| SVN_PASSWORD | WordPress.org থেকে তৈরি SVN password |

প্রথমটি Add secret দিয়ে save করুন, তারপর দ্বিতীয়টি আলাদাভাবে তৈরি করুন। Secret-গুলোর নাম ঠিক একই বড় হাতের অক্ষরে দিন। কোনো ব্যক্তিগত GitHub token প্রয়োজন নেই এই workflow-এর জন্য।

## ৬ — Actions চালু আছে কিনা

repository → Actions। GitHub enable করার বার্তা দিলে enable করুন। যদি Actions বন্ধ থাকে, Settings → Actions → General-এ repository policy অনুযায়ী Actions চালু করতে হবে। policy third-party action নিষিদ্ধ করলে 10up/action-wordpress-plugin-deploy এবং actions/checkout ব্যবহারের অনুমতি লাগবে।

## ৭ — প্রথম release প্রকাশ

আগে নিজের staging WordPress-এ মূল plugin ZIP পরীক্ষা করুন। আগের static review-তে পাওয়া permission/queue/privacy বিষয়গুলো docs/SOURCE-REVIEW.md-এ আছে; plugin কোড এই প্যাকেজেও অপরিবর্তিত। এই packaging কাজ থেকে production functionality verified বলা হচ্ছে না।

GitHub repository → Releases → Create a new release / Draft a new release।

- Choose a tag → লিখুন **1.0.0** → Create new tag
- Target → সোর্স ও workflow যে branch-এ আপলোড করেছেন, সাধারণত main
- Release title → Rejoyan CRM 1.0.0
- Description → First WordPress.org release.
- Set as a pre-release বন্ধ রাখুন
- সব প্রয়োজনীয় সোর্স এবং Secrets আছে নিশ্চিত করে **Publish release** চাপুন

Publish release হলো এই workflow-এর বাস্তব প্রকাশের trigger। এটি WordPress.org SVN-এ আপনার credentials দিয়ে commit করবে। এই নির্দেশিকা তৈরি করার সময় কোনো repository, secret, release বা SVN commit করা হয়নি।

## ৮ — ফলাফল দেখুন

Actions → Publish plugin to WordPress.org → সর্বশেষ run। সবুজ tick মানে workflow সফল; public page এবং Download আলাদা করে যাচাই করুন।

https://wordpress.org/plugins/rejoyan-crm-campaigns-for-woocommerce/

WordPress.org যদি release confirmation email/dashboard confirmation চায়, সেটি সম্পন্ন করুন। ইমেইল অনুসারে সব search result আপডেট হতে 72 ঘণ্টা লাগতে পারে; plugin page ও search indexing একই বিষয় নয়।

লাল চিহ্ন হলে ব্যর্থ step খুলুন। সাধারণ কারণ:

| Error | করণীয় |
|---|---|
| Version mismatch | tag, PHP Version, REJOYAN_CRM_VERSION এবং readme Stable tag মিলিয়ে দিন |
| Missing plugin source | সোর্স অতিরিক্ত বাইরের ফোল্ডারে আছে কিনা দেখুন; PHP root-এ দিন |
| Authentication failed | secret নাম/value, SVN password এবং approval-এর এক ঘণ্টা পরীক্ষা করুন |
| Forbidden | rejoyan9009-এর commit access ও exact slug পরীক্ষা করুন |
| Tag already exists | SVN-এ একই version আগে প্রকাশিত কিনা দেখুন; সফল release পুনরায় overwrite করবেন না |
| Workflow not running | .github/workflows/deploy.yml ঠিক জায়গায় আছে, release prerelease নয় এবং Actions চালু আছে কিনা দেখুন |

কোনো run আংশিক সফল হলে SVN tags/1.0.0 ও public page দেখে নিন; অন্ধভাবে নতুন release বা পুরোনো tag overwrite করবেন না।

## ৯ — ভবিষ্যতের update

পরবর্তী সংস্করণে 1.0.1 করার সময় তিন জায়গা একসঙ্গে বদলান: মূল PHP header Version, একই ফাইলের REJOYAN_CRM_VERSION, readme.txt-এর Stable tag। Changelog যোগ করুন। এরপর নতুন GitHub release tag **1.0.1** দিন। DB_VERSION শুধু প্রয়োজনীয় schema migration হলে বদলাবেন। একই released version-এর কোড overwrite করবেন না।

## ১০ — ছবিগুলো কোথায়

পরে আসল dashboard screenshot/banner/icon দিতে চাইলে repository-তে .wordpress-org/ ফোল্ডারে রাখুন। screenshot-1.png থেকে screenshot-6.png, banner-772x250.png, icon-128x128.png / icon-256x256.png ব্যবহার করুন। নতুন release-এর সময় deploy action এগুলো SVN-এর মূল assets/ এ পাঠাবে। এখন প্যাকেজে আসল artwork নেই। runtime CSS/JS-এর assets/ ফোল্ডার আলাদা; সেটি সরাবেন না।

## অফিসিয়াল রেফারেন্স

https://github.com/10up/action-wordpress-plugin-deploy
https://docs.github.com/en/actions/how-tos/write-workflows/choose-what-workflows-do/use-secrets
https://make.wordpress.org/meta/handbook/tutorials-guides/svn-access/
https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
