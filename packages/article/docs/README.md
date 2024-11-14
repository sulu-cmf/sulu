# Getting Started

## Installation

The recommended way to install this bundle is through [Composer](https://getcomposer.org/).

```bash
composer require sulu/article-bundle
```

## Create your first template

Go to your `config/templates/articles` directory a new file `article.xml` with the following content:

<details>
<summary>article.xml</summary>

```xml
<?xml version="1.0" ?>
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template.xsd">

    <key>article</key>

    <view>views/articles/article</view>
    <controller>Sulu\Bundle\ContentBundle\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>
    <cacheLifetime>604800</cacheLifetime>

    <meta>
        <title lang="en">Article</title>
        <title lang="de">Artikel</title>
    </meta>

    <properties>
        <property name="title" type="text_line" mandatory="true">
            <meta>
                <title lang="en">Title</title>
                <title lang="de">Titel</title>
            </meta>

            <params>
                <param name="headline" value="true"/>
            </params>

            <tag name="sulu.rlp.part"/>
            <tag name="sulu.search.field" role="title"/>
        </property>

        <property name="url" type="route">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>

            <tag name="sulu.rlp"/>
            <tag name="sulu.search.field" role="url"/>
        </property>
    </properties>
</template>

```

</details>
