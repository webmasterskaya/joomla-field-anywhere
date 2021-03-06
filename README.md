# Joomla! - Поля в любом месте
Пакет позволяет вывести данные из любого поля (`com_fields`) в любой части системы.
Поддерживается вывод полей, для следующих областей системы:
- Поле материала, менеджера материалов (`com_content.article`);
- Поле категории, менеджера материалов (`com_content.categories`);
- Поле пользователя, менеджера пользователей (`com_users.user`);
- Поле контакта, менеджера контактов (`com_contact.contact`);
- Поле категории, менеджера контактов (`com_contact.categories`).

### Где это может пригодиться
- Вывести только одно поле из материала, в модуле или другом материале;
- Вывести поле контакта в модул или материале;
- Вывести поле катешории в материале или контакте;
- ...

## Использование
Для вывода поля, необходимо вставить шорткод в редакторе контента

```text
{loadfield; context; fieldId[; srcId]}
```
,где:
- `loadfield` - служебное слово;
- `context` - область системы, из которой необходимо получить поле;
- `fieldId` - идентификатор поля, которое необходимо получить;
- `srcId` - идентификатор источника (если поле не задано, вызов будет равносилен штатному - `{field id}`).

## Планы
- Кнопка для редактора
- Добавить поддержку вывода сразу всех полей источника `{loadfield; context; [,*]; srcId}`;
- Добавить поддержку вывода группы полей `{loadfieldgroup; context; groupId[; srcId]}`;
- Добавить поддержку указания layout, для поля `{loadfield; context; groupId[; srcId][; layout]}`.
