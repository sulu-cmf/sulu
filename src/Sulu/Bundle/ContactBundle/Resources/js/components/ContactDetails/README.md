This component allows to assign different kind of contact details. This includes phone number, email addresses,
websites, faxes and social media profiles.

```javascript
const [value, setValue] = React.useState({
    emails: [{email: undefined, emailType: 1}],
    faxes: [{fax: undefined, faxType: 1}],
    phones: [{phone: undefined, phoneType: 1}],
    socialMedia: [{username: undefined, socialMediaType: 1}],
    websites: [{website: undefined, websiteType: 1}],
});

const changeHandler = (value) => {
    setValue(value);
};

<ContactDetails onChange={changeHandler} value={value} />
```
