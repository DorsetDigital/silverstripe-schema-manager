# ImageGallery schema

`ImageGallerySchema` describes a page or component containing a collection of images.

```php
use DorsetDigital\SchemaManager\Control\SchemaRegistry;
use DorsetDigital\SchemaManager\Model\Schema\ImageGallerySchema;

$gallerySchema = ImageGallerySchema::create(
    $page->AbsoluteLink(),
    $page->Title,
    $page->MetaDescription
);

foreach ($page->GalleryImages() as $image) {
    $gallerySchema->addImage(
        $image->getAbsoluteURL(),
        $image->Title,
        null,
        $image->getWidth(),
        $image->getHeight()
    );
}

SchemaRegistry::add($gallerySchema);
```

The gallery is linked to the automatic `WebPage` with `isPartOf` by default. For a dedicated gallery page, call `$gallerySchema->setMainEntityOfPage($page->AbsoluteLink())`. Individual images are `ImageObject` entries under `hasPart`, using `contentUrl` for the actual image asset. Name, description, width and height are optional.

A representative thumbnail can be set with:

```php
$gallerySchema->setThumbnail($thumbnailURL);
```

Pages containing multiple galleries can give each gallery a stable identifier using the optional fourth argument:

```php
$gallerySchema = ImageGallerySchema::create(
    $page->AbsoluteLink(),
    $gallery->Title,
    null,
    (string) $gallery->ID
);
```

This produces an entity ID such as `https://example.com/gallery/#imagegallery-42` while the default remains `#imagegallery`.

The builder also inherits `Schema::update()` for additional Schema.org properties.
