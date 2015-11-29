//@define Ext.calendar.data.EventMappings
/**
 * @class Ext.calendar.data.EventMappings
 * @extends Object
 * A simple object that provides the field definitions for Event records so that they can be easily overridden.
 */
Ext.ns('Ext.calendar.data');

Ext.calendar.data.EventMappings = {
    EventId: {
        name: 'EventId',
        mapping: 'id',
        type: 'int'
    },
    CalendarId: {
        name: 'CalendarId',
        mapping: 'cid',
        type: 'int'
    },
    Title: {
        name: 'Title',
        mapping: 'title',
        type: 'string'
    },
    Parent: {
        name: 'Parent',
        mapping: 'parent',
        type: 'string'
    },
    StartDate: {
        name: 'StartDate',
        mapping: 'start',
        type: 'date',
        dateFormat: 'c'
    },
    Start: {
        name: 'Start',
        mapping: 'start',
        type: 'string'
    },
    EndDate: {
        name: 'EndDate',
        mapping: 'end',
        type: 'date',
        dateFormat: 'c'
    },
    End: {
        name: 'End',
        mapping: 'end',
        type: 'string'
    },
    Important: {
        name: 'Important',
        mapping: 'important',
        type: 'string'
    },
    Notes: {
        name: 'Notes',
        mapping: 'notes',
        type: 'string'
    },
    Content: {
        name: 'Content',
        mapping: 'content',
        type: 'string'
    },
    Priority: {
        name: 'Priority',
        mapping: 'priority',
        type: 'string'
    },
    IsAllDay: {
        name: 'IsAllDay',
        mapping: 'ad',
        type: 'boolean'
    },
    Reminder: {
        name: 'Reminder',
        mapping: 'rem',
        type: 'string'
    },
    IsNew: {
        name: 'IsNew',
        mapping: 'n',
        type: 'boolean'
    },
    State: {
        name: 'State',
        mapping: 'state',
        type: 'string'
    },
    Responsible_id: {
        name: 'Responsible_id',
        mapping: 'responsible_id',
        type: 'string'
    },
    Responsible: {
        name: 'Responsible',
        mapping: 'responsible',
        type: 'string'
    },
    Follow_id: {
        name: 'Follow_id',
        mapping: 'follow_id',
        type: 'string'
    },
    Follow: {
        name: 'Follow',
        mapping: 'follow',
        type: 'string'
    },
    Location: {
        name: 'Location',
        mapping: 'location',
        type: 'string'
    },
    Url: {
        name: 'Url',
        mapping: 'url',
        type: 'string'
    },
    Owner: {
        name: 'Owner',
        mapping: 'owner',
        type: 'string'
    },
    Follower: {
        name: 'Follower',
        mapping: 'follower',
        type: 'string'
    },
    Relation: {
        name: 'Relation',
        mapping: 'relation',
        type: 'string'
    },
    Process: {
        name: 'Process',
        mapping: 'process',
        type: 'string'
    },
    Type: {
        name: 'Type',
        mapping: 'type',
        type: 'string'
    }
};
