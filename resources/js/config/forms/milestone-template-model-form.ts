import { CirclePlus } from 'lucide-react';

export const MilestoneTemplateModelFormConfig = {
    moduleTitle: 'Milestone Templates',
    title: 'Milestone Template Form',
    description: 'Create or edit milestone templates.',

    addButton: {
        id: 'add-milestone-template-button',
        label: 'Add Milestone',
        className:
            'rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700',
        icon: CirclePlus,
        type: 'button',
        variant: 'default',
    },

    fields: [
        {
            id: 'milestone-program',
            name: 'program_id',
            label: 'Program',
            type: 'select',
            optionsSource: 'programs',
            optionLabel: 'title',
            optionValue: 'id',
            required: true,
        },
        {
            id: 'milestone-title',
            name: 'title',
            label: 'Title',
            type: 'text',
            placeholder: 'Unit standard title',
            autoFocus: true,
            required: true,
        },
        {
            id: 'milestone-description',
            name: 'description',
            label: 'Description',
            type: 'textarea',
            placeholder: 'Describe the milestone',
        },
        {
            id: 'milestone-order',
            name: 'sort_order',
            label: 'Order',
            type: 'number',
            placeholder: '0',
        },
        {
            id: 'milestone-max-score',
            name: 'max_score',
            label: 'Max Score',
            type: 'number',
            placeholder: '100',
        },
        {
            id: 'milestone-pass-mark',
            name: 'pass_mark',
            label: 'Pass Mark',
            type: 'number',
            placeholder: '50',
        },
        {
            id: 'milestone-required',
            name: 'is_required',
            label: 'Required',
            type: 'select',
            required: true,
            options: [
                { label: 'Required', value: '1' },
                { label: 'Optional', value: '0' },
            ],
        },
        {
            id: 'milestone-active',
            name: 'is_active',
            label: 'Active',
            type: 'select',
            required: true,
            options: [
                { label: 'Active', value: '1' },
                { label: 'Inactive', value: '0' },
            ],
        },
        {
            id: 'milestone-expected-timing',
            name: 'expected_timing',
            label: 'Expected Timing',
            type: 'text',
            placeholder: 'Week 1, Module 2, Final week',
        },
    ],
};
