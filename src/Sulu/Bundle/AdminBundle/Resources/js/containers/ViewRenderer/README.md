The `ViewRenderer` is a simple component, which loads the registered view from its `ViewStore`, and renders it as a
React component and passes it the `Router` service, which is passed in as a property.

Registering a component in the `ViewStore` and rendering it using the `ViewRenderer` is shown in the following example:

```
const viewStore = require('./stores/ViewStore').default;
viewStore.clear(); // Just to make sure the ViewStore is empty, not needed in a real world application

const Component = ({router}) => (
    <div>
        <h1>View component</h1>
        <p>The next paragraph will show an attribute from the mocked Router.</p>
        <p>{router.attributes.content}</p>
    </div>
);
viewStore.add('view', Component);

// instead of this mocked Router you would usually use a real one
const router = {
    attributes: {
        content: 'Some trivial content!',
    },
};

<ViewRenderer name="view" router={router} />
```
