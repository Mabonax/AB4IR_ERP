type MilestoneTemplateTableRow = {
    is_required: boolean;
    is_active: boolean;
};

export const MilestoneTemplateTableConfig = {
    columns: [
        {
            label: 'Program',
            key: 'program_title',
            className: 'px-4 py-2 text-left',
        },
        { label: 'Title', key: 'title', className: 'px-4 py-2 text-left' },
        { label: 'Order', key: 'sort_order', className: 'px-4 py-2 text-left' },
        {
            label: 'Max Score',
            key: 'max_score',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Pass Mark',
            key: 'pass_mark',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Required',
            key: 'is_required',
            className: 'px-4 py-2 text-left',
            render: (row: MilestoneTemplateTableRow) =>
                row.is_required ? 'Required' : 'Optional',
        },
        {
            label: 'Active',
            key: 'is_active',
            className: 'px-4 py-2 text-left',
            render: (row: MilestoneTemplateTableRow) =>
                row.is_active ? 'Active' : 'Inactive',
        },
        {
            label: 'Timing',
            key: 'expected_timing',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Projects Using',
            key: 'projects_using_count',
            className: 'px-4 py-2 text-left',
        },
        {
            label: 'Actions',
            key: 'actions',
            isAction: true,
            className: 'px-4 py-2 text-left',
        },
    ],
};
