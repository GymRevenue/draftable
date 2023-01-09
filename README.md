# Laravel Draftable Models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/laravel-creative/draftable.svg?style=flat-square)](https://packagist.org/packages/laravel-creative/draftable)
[![Total Downloads](https://img.shields.io/packagist/dt/laravel-creative/draftable.svg?style=flat-square)](https://packagist.org/packages/laravel-creative/draftable)

Bring the power of Wordpress drafts posts to your Laravel application , with simple functions and steps

## Installation

You can install the package via composer:

```bash
composer require CapeAndBay/draftable
```

after installing the package go a head and run migrate.

``` php
php artisan migrate
```

In your model add

```php
use DraftableModel;
```

## Usage

#### Save model as draft

to save the model as draft you can use ```php $model->saveAsDraft() ``` method

```php
 $faker = Factory::create();
 $article = new Article();
 $article->title = $faker->paragraph(1);
 $article->content = $faker->paragraph;
 $article->saveAsDraft();
```

in case you want to save it in its own table but also you need to make a draft of this update you can use

```php
$article->saveWithDraft();
```

#### Assign the draft for owner

to assign the draft for specific owner

```php
$article::setOwner($user);
```

To get all drafts for specific owner

```php
 $draft_articles = Article::setOwner($user)->getAllDrafts();
```

#### Save data with the draft

you can save data with the draft like (publish_date) or anything else.

```php
$article->saveAsDraft();
$article->draft->setData('publish_date', Carbon::now()->addDay());
//you can get the data with this method
$article->draft->getData('publish_date');

```

#### Get Drafts for model

To get all drafts for the model use ```php Model::getAllDrafts() ```

```php
 $drafts = Article::getAllDrafts();
```

to get published drafts only use ```php Model::getPublishedDraft() ```

```php
 $publishedDrafts = Article::getPublishedDraft();
```

to get unpublished drafts only use ```php Model::getUnPublishedDraft() ```

```php
 $unpublished_draft_articles = Article::getUnPublishedDraft();
```

to publish the draft you can use
```php
$draft_articles->publish();

# Publish all drafts
$draft_articles = Article::getUnPublishedDraft();
foreach($draft_articles as $draft_articles) {
    $draft_articles->publish();
}
```

#### Get drafts for saved model

once you saved the model with

```php
$article->saveWithDraft();
```

you can access all drafts for this model with this method

```php
$article = Article::first();
dump($article->drafts);
```

if you want Eloquent model for specific draft use
```php
$draft->model()
#
$article_drafts[0]->model()
```

#### Restore specific draft for stored model

after selecting the draft for the model and you want to restore it as current published one
you can use ```php $draft->restore() ```

```php
$article = Article::first();
$article_draft = $article->drafts()->first();
$article_draft->restore();
```

### Testing

``` bash
composer test
```

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email mustafakhaled.dev@gmail.com instead of using the issue
tracker.

## Credits

- [Mustafa Khaled](https://github.com/mustafakhaleddev)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
