<h1 align="center">SuluArticleBundle</h1>

<p align="center">
    <a href="https://sulu.io/" target="_blank">
        <img width="30%" src="https://sulu.io/uploads/media/800x/00/230-Official%20Bundle%20Seal.svg?v=2-6&inline=1" alt="Official Sulu Bundle Badge">
    </a>
</p>

<p align="center">
    <a href="LICENSE" target="_blank">
        <img src="https://img.shields.io/github/license/sulu/SuluArticleBundle.svg" alt="GitHub license">
    </a>
    <a href="https://github.com/sulu/SuluArticleBundle/releases" target="_blank">
        <img src="https://img.shields.io/github/tag/sulu/SuluArticleBundle.svg" alt="GitHub tag (latest SemVer)">
    </a>
    <a href="https://github.com/sulu/SuluArticleBundle/actions" target="_blank">
        <img src="https://img.shields.io/github/actions/workflow/status/sulu/SuluArticleBundle/test-application.yaml" alt="Test workflow status">
    </a>
    <a href="https://app.circleci.com/pipelines/github/sulu/SuluArticleBundle" target="_blank">
        <img src="https://img.shields.io/circleci/build/github/sulu/SuluArticleBundle.svg?label=circleci" alt="CircleCI build">
    </a>
    <a href="https://github.com/sulu/sulu/releases" target="_blank">
        <img src="https://img.shields.io/badge/sulu%20compatibility-%3E=2.0-52b6ca.svg" alt="Sulu compatibility">
    </a>
</p>
<br/>

The **SuluArticleBundle** integrates a performance optimized way for managing articles in the [Sulu](https://sulu.io/)
content management system. In the context of this bundle, **articles are localized content-rich entities** that are
**manageable via the Sulu administration interface** and can be **rendered on a website delivered by Sulu**.
This makes them a good choice for managing things like blog posts, products or even recipes in a Sulu project.
In order to keep things clean, the bundle allows to **manage different types of articles via separated lists** in the
administration interface.

<br/>
<p align="center">
    <img width="80%" src="https://sulu.io/uploads/media/800x@2x/05/235-ezgif.gif?v=1" alt="Sulu Slideshow">
</p>
<br/>

The SuluArticleBundle is compatible with Sulu **starting from version 2.0**. Have a look at the `require` section in
the [composer.json](composer.json) to find an
**up-to-date list of the requirements** of the bundle.


## 🚀&nbsp; Installation and Documentation

Execute the following [composer](https://getcomposer.org/) commands to add the bundle to the dependencies of your
project:

```bash
composer require sulu/article-bundle
```

Afterwards, visit the [bundle documentation](docs) to
find out **how to set up and configure the SuluArticleBundle** to your specific needs.


## 💡&nbsp; Key Concepts

### Article Characteristics

Like Sulu pages, articles are **configured via templates** and can include additional **SEO and excerpt information**.
Moreover, articles support the same **drafting, publishing and versioning functionality** as provided by pages.
In contrast to Sulu pages, articles are **managed in a flat list** instead of a tree structure. Furthermore, unlike
the Sulu page tree, the article bundle is **optimized for managing a big number of articles**.


## ❤️&nbsp; Support and Contributions

The Sulu content management system is a **community-driven open source project** backed by various partner companies.
We are committed to a fully transparent development process and **highly appreciate any contributions**.

In case you have questions, we are happy to welcome you in our official [Slack channel](https://sulu.io/services-and-support).
If you found a bug or miss a specific feature, feel free to **file a new issue** with a respective title and description
on the the [sulu/SuluArticleBundle](https://github.com/sulu/SuluArticleBundle) repository.


## 📘&nbsp; License

The Sulu content management system is released under the under terms of the [MIT License](LICENSE).
