This container component offers a `textEditorRegistry`, which can be used to register text editors using a unique key.
There is also a `TextEditor` component which takes all the options passed to a `TextEditor` and an `adapter` prop, which
decides which `TextEditor` should be used.

```javascript
const CKEditor5 = require('../CKEditor5').default;

const [value, setValue] = React.useState('');

const textEditorRegistry = require('./registries/textEditorRegistry').default;
textEditorRegistry.clear();
textEditorRegistry.add('ckeditor5', CKEditor5);

const handleBlur = () => alert('Text editing finished!');

<div>
    <TextEditor adapter="ckeditor5" onBlur={handleBlur} onChange={setValue} value={value} />

    Output: <pre>{value}</pre>
</div>
```
