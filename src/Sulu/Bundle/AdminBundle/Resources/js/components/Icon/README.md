This is a simple component which renders icons. It uses the [Font Awesome Icon Toolkit](http://fontawesome.io/)
and our own icon font.

Pass a name (prefix `fa-` for Font Awesome or `su-` for Sulu) to the component,
and it will render the corresponding icon:

```
<Icon name="fa-floppy-o" />
```

It can also take an additional `className`, which will be added to the class of the resulting `span` tag:

```
<Icon name="fa-trash-o" className="special-icon" />
```

An icon can also have a `onClick` handler:

```
function handleClick() {
    alert('No action for you!');
}

<Icon name="fa-bars" onClick={handleClick} />
```

To use a Sulu icon just use the `su-` prefix:

```
<Icon name="su-link" />
```