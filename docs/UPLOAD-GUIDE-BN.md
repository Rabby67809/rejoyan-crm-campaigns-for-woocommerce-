# Rejoyan প্লাগিন: GitHub ও WordPress.org আপলোড নির্দেশিকা

এই প্যাকেজের মূল প্লাগিন কোড আপনার দেওয়া 1.0.0 ZIP-এর সঙ্গে হুবহু একই। README.md, docs ও Git-এর সেটিংস নতুন যোগ করা হয়েছে। কোনো GitHub repository এখনো তৈরি বা প্রকাশ করা হয়নি।

## ১. GitHub-এর সোর্স সাজানো

rejoyan-github-source-1.0.0.zip ডাউনলোড করে Extract করুন। এর ভিতরের rejoyan-crm-campaigns-for-woocommerce ফোল্ডারটি খুলুন। এই ফোল্ডারের **ভিতরের ফাইল ও ফোল্ডারগুলো** repository-এর মূল জায়গায় যাবে। ZIP ফাইলটি শুধু আপলোড করলে GitHub সোর্স খুলে দেখাবে না।

1. https://github.com/new খুলে নিজের অ্যাকাউন্টে লগইন করুন।
2. Repository name দিন: rejoyan-crm-campaigns-for-woocommerce
3. Public নির্বাচন করুন। এই প্যাকেজে README ও লাইসেন্স আগে থেকেই আছে; নতুন README/license তৈরির অপশন খালি রাখুন। Create repository চাপুন।
4. খালি repository-তে “uploading an existing file” লিংক ব্যবহার করুন। আগে থেকে ফাইল থাকলে Add file → Upload files।
5. Extract করা ফোল্ডারের ভিতরের সব ফাইল/ফোল্ডার আপলোড করুন। কম্পিউটারে drag-and-drop দিয়ে ফোল্ডারসহ দেওয়া সহজ। ফোনে Chrome-এর Desktop site ব্যবহার করা যায়, তবে ফাইল নির্বাচক ফোল্ডার আপলোড না করতে পারলে কম্পিউটার ব্যবহার করুন।
6. Commit message দিন: Import original plugin source 1.0.0. তারপর Commit changes।
7. repository-এর প্রথম পাতায় মূল PHP ফাইল, includes, assets, readme.txt ও README.md দেখা যাচ্ছে কিনা মিলিয়ে নিন। অতিরিক্ত বাইরের ফোল্ডারের ভিতরে সব সোর্স রাখবেন না।
8. GitHub ঠিকানা হবে https://github.com/YOUR-USERNAME/rejoyan-crm-campaigns-for-woocommerce — YOUR-USERNAME আপনার প্রকৃত GitHub নাম। এটি উদাহরণ, বিদ্যমান repository-এর যাচাই করা লিংক নয়।
9. ইচ্ছা করলে Releases → Draft a new release থেকে v1.0.0 tag দিন এবং আপনার মূল installable ZIP release asset হিসেবে যুক্ত করুন। GitHub-এর স্বয়ংক্রিয় Source code ZIP-এ docs থাকবে; মূল plugin ZIP ব্যবহার করলে পরিষ্কার installable package পাবেন।

## ২. কোন ফাইল কোথায় থাকবে

| ফাইল/ফোল্ডার | GitHub | WordPress.org SVN |
|---|---|---|
| rejoyan-crm-campaigns-for-woocommerce.php | repository মূল | trunk/ ও tags/1.0.0/ |
| includes/ | repository মূল | trunk/includes/ ও tags/1.0.0/includes/ |
| assets/css/ ও assets/js/ | repository মূলের assets/ | trunk/assets/ ও tags/1.0.0/assets/ |
| languages/ | repository মূল | trunk/languages/ ও tags/1.0.0/languages/ |
| readme.txt, changelog.txt, LICENSE.txt, uninstall.php | repository মূল | trunk/ ও tags/1.0.0/ |
| README.md, docs/, .gitignore, .gitattributes | repository মূল | release-এ কপি করবেন না |
| screenshot, banner, icon ছবি | চাইলে আলাদা সংরক্ষণ | SVN-এর একেবারে মূল assets/ |

languages ফোল্ডার এখন খালি। GitHub/Git খালি ফোল্ডার রাখে না; এটি না দেখালেও বর্তমান প্লাগিনের কার্যকরী ফাইল হারায়নি।

## ৩. WordPress.org: অনুমোদনের পর প্রকাশ

আপনার অনুমোদনের ইমেইলে থাকা **ঠিক SVN URL ও slug** ব্যবহার করুন। ZIP দেখে অনুমোদনের স্ট্যাটাস যাচাই করা যায় না। এই পরীক্ষায় পাবলিক প্লাগিন/SVN পেজও যাচাই করা যায়নি। ইমেইলের slug ভিন্ন হলে এই নির্দেশিকার URL অন্ধভাবে ব্যবহার করবেন না; নাম/Text Domain মিলিয়ে review team-এর নির্দেশ অনুসরণ করুন।

GitHub-এ আপলোড করলে WordPress.org আপডেট হয় না। WordPress.org-এর প্রকাশের জন্য SVN commit লাগে। প্রথম প্রকাশের আগে পরীক্ষার রিপোর্টের বিষয়গুলো দেখে staging সাইটে যাচাই করুন।

### Windows-এ GUI দিয়ে

1. WordPress নির্দেশিকায় উল্লিখিত TortoiseSVN-এর মতো SVN client ইনস্টল করুন।
2. কম্পিউটারে খালি rejoyan-svn নামে ফোল্ডার তৈরি করুন। Right click → SVN Checkout। Windows 11 হলে Show more options লাগতে পারে।
3. অনুমোদনের ইমেইল থেকে SVN URL paste করে Checkout করুন।
4. rejoyan-wordpress-svn-layout-1.0.0.zip Extract করুন। এর wordpress-svn-layout/trunk/ ফোল্ডারের **ভিতরের** ফাইলগুলো checkout করা trunk/ ফোল্ডারে কপি করুন।
5. মূল PHP যেন trunk/rejoyan-crm-campaigns-for-woocommerce.php হয়। trunk/rejoyan-crm-campaigns-for-woocommerce/rejoyan-crm-campaigns-for-woocommerce.php করবেন না।
6. checkout করা trunk-এ right click → TortoiseSVN → Add করে নতুন ফাইলগুলো যুক্ত করুন।
7. trunk-এ right click → TortoiseSVN → Branch/tag। To path হবে একই repository-এর /tags/1.0.0। Working copy থেকে copy বেছে নিন। client অনুযায়ী tag তৈরি আলাদা commit হতে পারে। tags/1.0.0 আগে থাকলে overwrite করবেন না।
8. checkout-এর root-এ SVN Commit করুন। Message: Release 1.0.0. WordPress.org username এবং account settings থেকে তৈরি SVN-specific password ব্যবহার করুন। পাসওয়ার্ড GitHub বা কোনো ফাইলে রাখবেন না।
9. release confirmation চালু থাকলে WordPress.org-এর confirmation email/dashboard নির্দেশ অনুসারে প্রকাশ নিশ্চিত করুন।
10. কিছু সময় পরে public plugin page ও Download পরীক্ষা করুন।

ZIP-এর tags/1.0.0/ হলো ঠিক ফাইলবিন্যাসের নমুনা। SVN history রাখার জন্য working-copy trunk থেকে Branch/tag দিয়ে tag তৈরি করাই ভালো। আগে থেকে tags/1.0.0 থাকলে প্রথম প্রকাশের এই ধাপ চালাবেন না; নতুন version ব্যবহার করুন।

### Command line জানা থাকলে: প্রথম release

নিচের URL শুধুমাত্র ইমেইলের URL একই হলে ব্যবহার করুন। checkout-এর trunk খালি ধরে উদাহরণ দেওয়া হচ্ছে।

```bash
svn checkout https://plugins.svn.wordpress.org/rejoyan-crm-campaigns-for-woocommerce/ rejoyan-svn
# এখন plugin-এর আসল ফাইলগুলো rejoyan-svn/trunk/ এ কপি করুন।
cd rejoyan-svn
svn add trunk/*
svn copy trunk tags/1.0.0
svn status
svn commit -m "Release 1.0.0" --username YOUR-WORDPRESS-USERNAME
```

পাসওয়ার্ড prompt এ SVN password দিন। zip বা github-source-এর বাইরের পুরো ফোল্ডার trunk-এ দেবেন না। ভবিষ্যতে version বাড়ালে মূল PHP header, REJOYAN_CRM_VERSION, readme Stable tag ও SVN tag একসঙ্গে মিলিয়ে বদলাতে হবে।

## ৪. Screenshot, banner ও icon

বর্তমান ZIP-এ listing-এর ছবি নেই। WordPress.org SVN-এর মূল assets/ এ দিন:

| নাম | মাপ/বিষয় |
|---|---|
| banner-772x250.png | 772 × 250 pixels |
| banner-1544x500.png | ঐচ্ছিক 1544 × 500 retina banner |
| icon-128x128.png | 128 × 128 pixels |
| icon-256x256.png | 256 × 256 pixels |
| screenshot-1.png | Dashboard |
| screenshot-2.png | Customer Email Center |
| screenshot-3.png | Offer Template Studio |
| screenshot-4.png | Delivery Analytics |
| screenshot-5.png | Social Studio |
| screenshot-6.png | System Health |

নিজের staging সাইটে আসল প্লাগিন চালিয়ে screenshot নিন। গ্রাহকের ব্যক্তিগত তথ্য ঢেকে দিন। এগুলো প্লাগিনের CSS/JS assets-এর সঙ্গে গুলিয়ে ফেলবেন না। ছবি তৈরি করা হয়নি; তালিকাটি কী যোগ করবেন তার নির্দেশনা।

## ৫. YouTube-এর কথা

YouTube-এ PHP/CSS/JS সোর্স রাখতে হয় না। আপনি যদি ভিডিও tutorial চান, নিচের ক্রমে screen recording করুন বা কোনো tutorial অনুসরণ করুন:

1. ZIP Extract দেখানো।
2. GitHub repository তৈরি ও ভিতরের সোর্স upload।
3. GitHub-এর file list মিলিয়ে দেখা।
4. SVN Checkout, trunk-এ কপি, version tag ও Commit।
5. WordPress.org পেজ দেখা এবং WordPress test সাইটে install/activate।

এটি ভিডিও বানানো বা YouTube-এ প্রকাশ করা নয়; একই কাজ নিজে করার লিখিত নির্দেশিকা।

## ৬. নিজের test সাইটে যাচাই

- WordPress 6.5+, PHP 7.4+, WooCommerce 8.2+ অনুযায়ী staging তৈরি করুন; বাস্তবে যে version পরীক্ষা হয়েছে সেটিই compatibility metadata-তে লিখুন।
- মূল দেওয়া installable ZIP দিয়ে Plugins → Add New → Upload Plugin → Install → Activate করুন।
- Settings-এ sender বসিয়ে নিজের ইমেইলে test পাঠান।
- ছোট test customer audience দিয়ে draft, queue, scheduled campaign, retry ও cancel দেখুন।
- Unsubscribe করা customer যেন আর না পায়, পরীক্ষা করুন।
- একাধিক queue worker চললে একই ইমেইল দুইবার যায় কিনা পরীক্ষা করুন।
- HPOS চালু করে invoice এবং order/customer panels পরীক্ষা করুন।
- Privacy exporter/eraser ও uninstall cleanup শুধু disposable test data দিয়ে পরীক্ষা করুন।

## অফিসিয়াল নির্দেশিকা

- GitHub upload: https://docs.github.com/en/repositories/working-with-files/managing-files/adding-a-file-to-a-repository
- WordPress SVN: https://developer.wordpress.org/plugins/wordpress-org/how-to-use-subversion/
- WordPress listing assets: https://developer.wordpress.org/plugins/wordpress-org/plugin-assets/
