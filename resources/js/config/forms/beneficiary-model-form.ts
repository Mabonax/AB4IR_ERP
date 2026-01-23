import { CirclePlus } from "lucide-react";

export const BeneficiaryModelFormConfig = {
    moduleTitle: "Beneficiaries",
    title: "Beneficiary Form",
    description: "Fill in the details to add or edit a beneficiary.",

    addButton: {
        id: "add-beneficiary-button",
        label: "Add Beneficiary",
        className: "rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-700",
        icon: CirclePlus,
        type: "button",
        variant: "default",
    },

    fields: [
        // =========================
        // PERSONAL INFO
        // =========================
        {
            id: "beneficiary-name",
            name: "name",
            label: "First Name",
            type: "text",
            placeholder: "Enter first name",
            autoFocus: true,
            required: true,
        },
        {
            id: "beneficiary-surname",
            name: "surname",
            label: "Surname",
            type: "text",
            placeholder: "Enter surname",
            required: true,
        },
        {
            id: "beneficiary-dob",
            name: "dob",
            label: "Date of Birth",
            type: "date",
            required: true,
        },
        {
            id: "beneficiary-age",
            name: "age",
            label: "Age",
            type: "number",
            min: 0,
            required: true,
        },

        // =========================
        // IDENTIFICATION
        // =========================
        {
            id: "beneficiary-id-number",
            name: "id_number",
            label: "ID Number",
            type: "text",
            placeholder: "13-digit ID number",
            required: true,
        },
        {
            id: "beneficiary-email",
            name: "email",
            label: "Email",
            type: "email",
            placeholder: "Enter email address",
            required: true,
        },
        {
            id: "beneficiary-phone",
            name: "phone",
            label: "Phone Number",
            type: "tel",
            placeholder: "Enter phone number",
        },

        // =========================
        // DEMOGRAPHICS
        // =========================
        {
            id: "beneficiary-gender",
            name: "gender",
            label: "Gender",
            type: "select",
            required: true,
            options: [
                { label: "Male", value: "male" },
                { label: "Female", value: "female" },
            ],
        },

        // =========================
        // ADDRESS
        // =========================
        {
            id: "beneficiary-street-address",
            name: "street_address",
            label: "Street Address",
            type: "textarea",
            rows: 2,
        },
        {
            id: "beneficiary-address-line-2",
            name: "address_line_2",
            label: "Address Line 2",
            type: "text",
        },
        {
            id: "beneficiary-city",
            name: "city",
            label: "City",
            type: "text",
        },
        {
            id: "beneficiary-province",
            name: "province_id",
            label: "Province",
            type: "select",
            optionsSource: "provinces", // passed from page props
            optionLabel: "name",
            optionValue: "id",
        },
        {
            id: "beneficiary-postal-code",
            name: "postal_code",
            label: "Postal Code",
            type: "text",
        },

        // =========================
        // EDUCATION
        // =========================
        {
            id: "beneficiary-qualification",
            name: "highest_qualification",
            label: "Highest Qualification",
            type: "text",
        },

   // =========================
    // NEXT OF KIN
    // =========================
    { id: "nok_name", name: "nok_name", label: "Next of Kin Name", type: "text", required: true },
    { id: "nok_surname", name: "nok_surname", label: "Next of Kin Surname", type: "text", required: true },
    { id: "nok_relationship", name: "nok_relationship", label: "Relationship", type: "text", required: true },
    { id: "nok_phone", name: "nok_phone", label: "Next of Kin Phone", type: "tel" },
    { id: "nok_email", name: "nok_email", label: "Next of Kin Email", type: "email" },
    ],
};
