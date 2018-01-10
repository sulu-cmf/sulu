The `Datagrid` is responsible for rendering data in a table view. One of its properties is the `store`, which has to be
created outside, and be passed to the `Datagrid`. The `DatagridStore` is responsible for loading a page from a
REST API. The presentation of the `Datagrid` is handled by its adapters. An adapter is the glue which connects a basic
component like the `Table` to the `Datagrid`. The available adapters for a `Datagrid` can be set using the `views`
property. Keep in mind that an adapter has to be defined and added to the `adapterStore` before it is used by a
rendered `Datagrid`.

```javascript static
const TableAdapter = require('./adapters/TableAdapter');
const adapterStore = require('./stores/AdapterStore');
const store = new DatagridStore('snippets');

adapterStore.add('table', TableAdapter);

<Datagrid store={store} views={['table']} />

store.selections; // returns the IDs of the selected items
store.destroy();
```

The `Datagrid` also takes control of the store, and handles loading other pages and selecting of items. The `selections`
property can be used to retrieve the IDs of the currently selected items.

After the store is not used anymore, its `destroy` method should be called, because there are some observations, which
have to be cancelled.

The `Datagrid` component also takes an `onRowEditClick` callback, which is executed when a row has been clicked with
the intent of editing it. The callback gets one parameter, which is the ID of the row to edit.

### Adapters

Sulu comes with a few adapters prebuilt:

| Adapter                   | Description                                                              |
| ------------------------- | ------------------------------------------------------------------------ |
| TableAdapter              | Integrates the [`Table`](#table) component                               |
| ColumnListAdapter         | Integrates the [`ColumnList`](#columnlist) component                     |
| FolderAdapter             | Integrates the [`FolderList`](#folderlist) component                     |
| MediaCardSelectionAdapter | Integrates the [`MediaCard`](#mediacard) component with a selection icon |
| MediaCardOverviewAdapter  | Integrates the [`MediaCard`](#mediacard) component with a edit icon      |

The adapters are only responsible for displaying the information they get passed from the datagrid. All the other
datagrid functionality is built into the `Datagrid` component.

However, the different adapters have slightly different requirements regarding loading and storing the data from the
server. Therefore the adapters can define which `LoadingStrategy` and which `StructureStrategy` they are using. These
two interfaces will be explained in the next sections.

#### LoadingStrategies

The `LoadingStrategy` is only responsible for loading the data from the server. Its most important method has the
following interface:

```javascript static
load(data: Array<Object>, resourceKey: string, options: LoadOptions, enhanceItem: ItemEnhancer)
```

This method gets the array into which the loaded data has to be written. The `resourceKey` defines for which entity
the data is loaded, and is required because the `LoadingStrategies` make use of the
[`ResourceRequester` service](#resourcerequester). The `options` can contain more parameters being added to the URL the
request will be sent to, e.g. the currently active element will be added as `parent` automatically. Finally the
`enhanceItem` function can be passed for modifying all the items before writing them into the `data` array. This
function will be retrieved from the `StructureStrategy`, which will be explained in the next section.

There are also the `initialize` and `reset` method on the interface. Both of them get the `datagridStore` as a
paremeter and can modify it based on their needs. The `initialize` method will be called everytime the
`LoadingStrategy` is initialized, whereby the `reset` method is only called when there was a previous adapter and the
datagrid switches to a new adapter.

Sulu is delivered with a few `LoadingStrategy` implementations:

| Name                     | Description                                                        | Pagination Component                    |
| ------------------------ | ------------------------------------------------------------------ | --------------------------------------- |
| FullLoadingStrategy      | Does not do any pagination and simply loads all available items    | None                                    |
| InfiniteLoadingStrategy  | Loads the next few items and appends them to the `data` array      | [`InfiniteScroller`](#infinitescroller) |
| PaginatedLoadingStrategy | Loads the next few items and replaces the ones in the `data` array | [`Pagination`](#pagination)             |

The list also contains the recommended pagination component to use with each strategy. Make sure that the adapters you
are developing are using the correct pagination according to their `LoadingStrategy`. Note that it is the
responsibility of the adapter to display a pagination component.

#### StructureStrategies

This strategy is responsible for defining how the data in the array has to be structured. Sulu comes with two different
`StructureStrategy` implementations. The `FlatStructureStrategy` holds a simple array of objects containing the items.
The `TreeStructureStrategy` is used when some kind of tree has to be built (e.g. when using the adapter for the
[`ColumnList`](#columnlist)).

The `StructureStrategy`'s most important method is the `getData` method, which takes a parent as argument. Based on
this it has to return flat array of objects. The implementation of the `FlatStructureStrategy` can therefore simply
return its `data` array, but the `TreeStructureStrategy` has to return only the children of the given parent. This is
also the `data` is passed to the `load` method of the `LoadingStrategy`.

The data has to be hold by a variable called `data`, because the data passed to the adapter is directly accessed this
way.

Furthermore the `StructureStrategy` has to define a parameterless `clear` method, which will be called when the adapter
is changed and has to remove all items. Last but not least there is the `enhanceItem` method, which will be passed to
the already described `LoadingStrategy` to enable the `StructureStrategy` to modify the data returned from the server
before being written in its `data` array. This is necessary to create the necessary envelope for the items in the
`TreeStructureStrategy`.
