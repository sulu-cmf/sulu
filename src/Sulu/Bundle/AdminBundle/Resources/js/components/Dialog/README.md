The `Dialog` component let's you display some content above everything else.
It renders depending on the passed property and request being closed through a callback.

```
initialState = {open: false};

const onConfirm = () => {
    /* do confirm things */
    setState({open: false});
};

const onCancel = () => {
    /* do cancel things */
    setState({open: false});
};

<div>
    <button onClick={() => setState({open: true})}>Open dialog</button>
    <Dialog
        title="Question?"
        onCancel={onCancel}
        onConfirm={onConfirm}
        cancelText="No"
        confirmText="Yes"
        open={state.open}>
        You've got a question in here.
        Yes or no?
    </Dialog>
</div>
```
