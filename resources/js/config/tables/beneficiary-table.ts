import { Eye, PencilIcon, Trash2 } from "lucide-react";

export const BeneficiaryTableConfig = {
    columns: [
        { label: 'Name', key: 'name', className: 'px-4 py-2 text-left' },
        { label: 'Surname', key: 'surname', className: 'px-4 py-2 text-left' },
        { label: 'Email', key: 'email', className: 'px-4 py-2 text-left' },
        { label: 'Actions', key: 'actions', isAction: true, className: 'px-4 py-2 text-left' },
    ],

    actions: [
        { label: '', icon: 'Eye', route: 'beneficiaries.show' },
        { label: '', icon: 'PencilIcon', route: 'beneficiaries.edit' },
        
    ],
};
