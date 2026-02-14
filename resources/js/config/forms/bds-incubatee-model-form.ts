import { CirclePlus } from "lucide-react";

export const BdsIncubateeModelFormConfig = {
  title: "Incubatee Form",
  description: "Capture incubatee details manually.",
  addButton: {
    id: "add-incubatee-button",
    label: "Add Incubatee",
    className: "rounded-lg bg-red-600 px-4 py-2 text-white hover:bg-red-700",
    icon: CirclePlus,
    type: "button",
    variant: "default",
  },
  fields: [
    { id: "full-name", name: "full_name", label: "Full Name", type: "text", required: true },
    { id: "id-number", name: "id_number", label: "ID Number", type: "text", required: true },
    { id: "gender", name: "gender", label: "Gender", type: "select", required: true, options: [
      { label: "Male", value: "Male" },
      { label: "Female", value: "Female" },
      { label: "Other", value: "Other" },
    ]},
    { id: "mobile-number", name: "mobile_number", label: "Mobile Number", type: "tel", required: true },
    { id: "email", name: "email", label: "Email Address", type: "email", required: true },
    { id: "company-name", name: "company_name", label: "Company Name", type: "text", required: true },
    { id: "company-registration-number", name: "company_registration_number", label: "Company Registration Number", type: "text", required: true },
    { id: "position-in-company", name: "position_in_company", label: "Position in Company", type: "text" },
    { id: "majority-shareholding", name: "majority_shareholding", label: "Majority Shareholding", type: "text" },
    { id: "current-number-of-employees", name: "current_number_of_employees", label: "Current Number of Employees", type: "number", required: true },
    { id: "physical-address", name: "physical_address", label: "Physical Address", type: "textarea" },
    { id: "website-address", name: "website_address", label: "Website Address", type: "text" },
    { id: "years-in-operation", name: "years_in_operation", label: "Years in Operation", type: "number", required: true },
    { id: "province-id", name: "province_id", label: "Province", type: "select", optionsSource: "provinces", optionLabel: "name", optionValue: "id", required: true },
    { id: "has-business-plan", name: "has_business_plan", label: "Has Business Plan?", type: "select", required: true, options: [
      { label: "Yes", value: "1" },
      { label: "No", value: "0" },
    ]},
    { id: "relevant-skill-set", name: "relevant_skill_set", label: "Relevant Skill Set", type: "textarea", required: true },
    { id: "technology-product-service", name: "technology_product_service", label: "Technology/Product/Service", type: "textarea", required: true },
    { id: "technology-stage-of-development", name: "technology_stage_of_development", label: "Stage of Development", type: "textarea", required: true },
    { id: "status", name: "status", label: "Status", type: "select", required: true, options: [
      { label: "Active", value: "active" },
      { label: "Inactive", value: "inactive" },
    ]},
  ],
};

