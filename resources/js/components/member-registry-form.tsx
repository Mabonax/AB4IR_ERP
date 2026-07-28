import { Link, useForm } from "@inertiajs/react";
import type { ReactNode } from "react";

import { Button } from "@/components/ui/button";
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";

type NamedOption = { id: number; name: string };
export type AssignmentOption = { id: number; name?: string; title?: string };

type MemberFormData = {
  first_name: string;
  last_name: string;
  id_number: string;
  date_of_birth: string;
  gender: string;
  phone: string;
  email: string;
  physical_address: string;
  province_id: string;
  municipality_id: string;
  region_id: string;
  township_id: string;
  ward_id: string;
  branch_id: string;
  member_type: string;
  status: string;
  disability_status: boolean;
  youth_indicator: boolean;
  veteran_indicator: boolean;
  household_size: string;
  dependants: string;
  employment: {
    employment_status: string;
    employer: string;
    occupation: string;
    industry: string;
    years_experience: string;
    monthly_income_band: string;
  };
  qualifications: Array<{
    qualification_type: string;
    institution: string;
    qualification_name: string;
    field_of_study: string;
    nqf_level: string;
    start_date: string;
    end_date: string;
    completed_flag: boolean;
    completion_year: string;
  }>;
  skills: Array<{
    skill_name: string;
    category: string;
    proficiency_level: string;
    years_experience: string;
  }>;
  work_experiences: Array<{
    employer: string;
    position: string;
    industry: string;
    start_date: string;
    end_date: string;
    current_employer_flag: boolean;
    responsibilities: string;
  }>;
  interests: Array<{
    interest_type: string;
    opportunity_category: string;
    notes: string;
  }>;
  assignments: Array<{
    assignment_type: string;
    assignable_id: string;
    member_role: string;
    started_at: string;
    ended_at: string;
    notes: string;
  }>;
};

export type MemberFormOptions = {
  provinces: NamedOption[];
  municipalities: NamedOption[];
  regions: NamedOption[];
  townships: NamedOption[];
  wards: NamedOption[];
  branches: NamedOption[];
  memberTypes: string[];
  memberStatuses: string[];
  genders: string[];
  qualificationTypes: string[];
  skillLevels: string[];
  employmentStatuses: string[];
  interestTypes: string[];
  incomeBands: string[];
};

const emptyQualification = () => ({
  qualification_type: "",
  institution: "",
  qualification_name: "",
  field_of_study: "",
  nqf_level: "",
  start_date: "",
  end_date: "",
  completed_flag: false,
  completion_year: "",
});

const emptySkill = () => ({
  skill_name: "",
  category: "",
  proficiency_level: "",
  years_experience: "",
});

const emptyWorkExperience = () => ({
  employer: "",
  position: "",
  industry: "",
  start_date: "",
  end_date: "",
  current_employer_flag: false,
  responsibilities: "",
});

const emptyInterest = () => ({
  interest_type: "",
  opportunity_category: "",
  notes: "",
});

const emptyAssignment = () => ({
  assignment_type: "",
  assignable_id: "",
  member_role: "",
  started_at: "",
  ended_at: "",
  notes: "",
});

const defaultData: MemberFormData = {
  first_name: "",
  last_name: "",
  id_number: "",
  date_of_birth: "",
  gender: "",
  phone: "",
  email: "",
  physical_address: "",
  province_id: "",
  municipality_id: "",
  region_id: "",
  township_id: "",
  ward_id: "",
  branch_id: "",
  member_type: "",
  status: "active",
  disability_status: false,
  youth_indicator: false,
  veteran_indicator: false,
  household_size: "",
  dependants: "",
  employment: {
    employment_status: "",
    employer: "",
    occupation: "",
    industry: "",
    years_experience: "",
    monthly_income_band: "",
  },
  qualifications: [],
  skills: [],
  work_experiences: [],
  interests: [],
  assignments: [],
};

function InputGroup({
  label,
  children,
  error,
  className = "",
}: {
  label: string;
  children: ReactNode;
  error?: string;
  className?: string;
}) {
  return (
    <div className={`grid gap-2 ${className}`}>
      <Label>{label}</Label>
      {children}
      {error ? <p className="text-xs text-red-600">{error}</p> : null}
    </div>
  );
}

export function MemberRegistryForm({
  mode,
  initialData,
  options,
  assignmentOptions,
  memberId,
}: {
  mode: "create" | "edit";
  initialData?: Partial<MemberFormData>;
  options: MemberFormOptions;
  assignmentOptions: Record<string, AssignmentOption[]>;
  memberId?: number;
}) {
  const form = useForm<MemberFormData>({
    ...defaultData,
    ...initialData,
    employment: {
      ...defaultData.employment,
      ...(initialData?.employment ?? {}),
    },
    qualifications: initialData?.qualifications?.length ? initialData.qualifications : [],
    skills: initialData?.skills?.length ? initialData.skills : [],
    work_experiences: initialData?.work_experiences?.length ? initialData.work_experiences : [],
    interests: initialData?.interests?.length ? initialData.interests : [],
    assignments: initialData?.assignments?.length ? initialData.assignments : [],
  });

  const { data, setData, post, put, processing, errors } = form;

  const hasSectionErrors = (section: "qualifications" | "skills" | "work_experiences" | "interests" | "assignments") =>
    Object.keys(errors).some((key) => key.startsWith(`${section}.`));

  const fieldError = (path: string) => errors[path as keyof typeof errors];

  const visibleErrors = Object.entries(errors)
    .filter(([, value]) => Boolean(value))
    .map(([key, value]) => `${key.replace(/\.\d+\./g, " - ").replace(/\./g, " ")}: ${value}`);

  const buildPayload = (payload: MemberFormData): MemberFormData => ({
    ...payload,
    qualifications: payload.qualifications.filter((qualification) =>
      [
        qualification.qualification_type,
        qualification.institution,
        qualification.qualification_name,
        qualification.field_of_study,
        qualification.nqf_level,
        qualification.start_date,
        qualification.end_date,
        qualification.completion_year,
      ].some((value) => value.trim() !== "") || qualification.completed_flag
    ),
    skills: payload.skills.filter((skill) =>
      [skill.skill_name, skill.category, skill.proficiency_level, skill.years_experience].some((value) => value.trim() !== "")
    ),
    work_experiences: payload.work_experiences.filter((experience) =>
      [
        experience.employer,
        experience.position,
        experience.industry,
        experience.start_date,
        experience.end_date,
        experience.responsibilities,
      ].some((value) => value.trim() !== "") || experience.current_employer_flag
    ),
    interests: payload.interests.filter((interest) =>
      [interest.interest_type, interest.opportunity_category, interest.notes].some((value) => value.trim() !== "")
    ),
    assignments: payload.assignments.filter((assignment) =>
      [
        assignment.assignment_type,
        assignment.assignable_id,
        assignment.member_role,
        assignment.started_at,
        assignment.ended_at,
        assignment.notes,
      ].some((value) => value.trim() !== "")
    ),
  });

  const submit = (event: React.FormEvent) => {
    event.preventDefault();

    if (mode === "create") {
      form.transform(buildPayload);
      post("/members");
      return;
    }

    form.transform(buildPayload);
    put(`/members/${memberId}`);
  };

  const setListItem = <K extends keyof Pick<MemberFormData, "qualifications" | "skills" | "work_experiences" | "interests" | "assignments">>(
    key: K,
    index: number,
    field: string,
    value: string | boolean
  ) => {
    const current = [...data[key]] as Array<Record<string, string | boolean>>;
    current[index] = { ...current[index], [field]: value };
    setData(key as never, current as never);
  };

  const addListItem = <K extends keyof Pick<MemberFormData, "qualifications" | "skills" | "work_experiences" | "interests" | "assignments">>(
    key: K,
    value: MemberFormData[K][number]
  ) => {
    setData(key as never, [...data[key], value] as never);
  };

  const removeListItem = <K extends keyof Pick<MemberFormData, "qualifications" | "skills" | "work_experiences" | "interests" | "assignments">>(
    key: K,
    index: number
  ) => {
    setData(key as never, data[key].filter((_, itemIndex) => itemIndex !== index) as never);
  };

  const assignmentChoices = (assignmentType: string) => assignmentOptions[assignmentType] ?? [];

  return (
    <form onSubmit={submit} className="space-y-6">
      {visibleErrors.length ? (
        <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          <div className="font-semibold">Fix the highlighted member form errors.</div>
          <ul className="mt-2 list-disc pl-5">
            {visibleErrors.slice(0, 8).map((message) => (
              <li key={message}>{message}</li>
            ))}
          </ul>
        </div>
      ) : null}

      <Card>
        <CardHeader>
          <CardTitle>{mode === "create" ? "Register Member" : "Edit Member"}</CardTitle>
          <CardDescription>Capture township-level human capital, opportunity readiness, and organisational placement data.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-2">
          <InputGroup label="First Name" error={errors.first_name}>
            <Input value={data.first_name} onChange={(event) => setData("first_name", event.target.value)} />
          </InputGroup>
          <InputGroup label="Last Name" error={errors.last_name}>
            <Input value={data.last_name} onChange={(event) => setData("last_name", event.target.value)} />
          </InputGroup>
          <InputGroup label="ID Number" error={errors.id_number}>
            <Input value={data.id_number} onChange={(event) => setData("id_number", event.target.value)} />
          </InputGroup>
          <InputGroup label="Date of Birth" error={errors.date_of_birth}>
            <Input type="date" value={data.date_of_birth} onChange={(event) => setData("date_of_birth", event.target.value)} />
          </InputGroup>
          <InputGroup label="Gender" error={errors.gender}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.gender} onChange={(event) => setData("gender", event.target.value)}>
              <option value="">Select gender</option>
              {options.genders.map((gender) => (
                <option key={gender} value={gender}>
                  {gender}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Phone" error={errors.phone}>
            <Input value={data.phone} onChange={(event) => setData("phone", event.target.value)} />
          </InputGroup>
          <InputGroup label="Email" error={errors.email}>
            <Input type="email" value={data.email} onChange={(event) => setData("email", event.target.value)} />
          </InputGroup>
          <InputGroup label="Physical Address" error={errors.physical_address} className="md:col-span-2">
            <textarea className="min-h-24 rounded-md border bg-card px-3 py-2 text-sm" value={data.physical_address} onChange={(event) => setData("physical_address", event.target.value)} />
          </InputGroup>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Classification and Geography</CardTitle>
          <CardDescription>Support reporting from province down to branch level.</CardDescription>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-3">
          <InputGroup label="Member Type" error={errors.member_type}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.member_type} onChange={(event) => setData("member_type", event.target.value)}>
              <option value="">Select type</option>
              {options.memberTypes.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Status" error={errors.status}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.status} onChange={(event) => setData("status", event.target.value)}>
              {options.memberStatuses.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Province" error={errors.province_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.province_id} onChange={(event) => setData("province_id", event.target.value)}>
              <option value="">Select province</option>
              {options.provinces.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Municipality" error={errors.municipality_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.municipality_id} onChange={(event) => setData("municipality_id", event.target.value)}>
              <option value="">Select municipality</option>
              {options.municipalities.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Region" error={errors.region_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.region_id} onChange={(event) => setData("region_id", event.target.value)}>
              <option value="">Select region</option>
              {options.regions.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Township" error={errors.township_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.township_id} onChange={(event) => setData("township_id", event.target.value)}>
              <option value="">Select township</option>
              {options.townships.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Ward" error={errors.ward_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.ward_id} onChange={(event) => setData("ward_id", event.target.value)}>
              <option value="">Select ward</option>
              {options.wards.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Branch" error={errors.branch_id}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.branch_id} onChange={(event) => setData("branch_id", event.target.value)}>
              <option value="">Select branch</option>
              {options.branches.map((row) => (
                <option key={row.id} value={row.id}>
                  {row.name}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Household Size" error={errors.household_size}>
            <Input type="number" min="0" value={data.household_size} onChange={(event) => setData("household_size", event.target.value)} />
          </InputGroup>
          <InputGroup label="Dependants" error={errors.dependants}>
            <Input type="number" min="0" value={data.dependants} onChange={(event) => setData("dependants", event.target.value)} />
          </InputGroup>
          <div className="flex items-center gap-6 md:col-span-3">
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={data.disability_status} onChange={(event) => setData("disability_status", event.target.checked)} />
              Disability Status
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={data.youth_indicator} onChange={(event) => setData("youth_indicator", event.target.checked)} />
              Youth Indicator
            </label>
            <label className="flex items-center gap-2 text-sm">
              <input type="checkbox" checked={data.veteran_indicator} onChange={(event) => setData("veteran_indicator", event.target.checked)} />
              Veteran Indicator
            </label>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Employment Status</CardTitle>
        </CardHeader>
        <CardContent className="grid gap-4 md:grid-cols-3">
          <InputGroup label="Employment Status" error={errors["employment.employment_status"]}>
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.employment.employment_status} onChange={(event) => setData("employment", { ...data.employment, employment_status: event.target.value })}>
              <option value="">Select status</option>
              {options.employmentStatuses.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </InputGroup>
          <InputGroup label="Employer">
            <Input value={data.employment.employer} onChange={(event) => setData("employment", { ...data.employment, employer: event.target.value })} />
          </InputGroup>
          <InputGroup label="Occupation">
            <Input value={data.employment.occupation} onChange={(event) => setData("employment", { ...data.employment, occupation: event.target.value })} />
          </InputGroup>
          <InputGroup label="Industry">
            <Input value={data.employment.industry} onChange={(event) => setData("employment", { ...data.employment, industry: event.target.value })} />
          </InputGroup>
          <InputGroup label="Years Experience">
            <Input type="number" min="0" value={data.employment.years_experience} onChange={(event) => setData("employment", { ...data.employment, years_experience: event.target.value })} />
          </InputGroup>
          <InputGroup label="Monthly Income Band">
            <select className="rounded-md border bg-card px-3 py-2 text-sm" value={data.employment.monthly_income_band} onChange={(event) => setData("employment", { ...data.employment, monthly_income_band: event.target.value })}>
              <option value="">Select band</option>
              {options.incomeBands.map((value) => (
                <option key={value} value={value}>
                  {value}
                </option>
              ))}
            </select>
          </InputGroup>
        </CardContent>
      </Card>

      <RepeaterCard
        title="Qualifications"
        description="Capture multiple formal qualifications per member."
        addLabel="Add Qualification"
        onAdd={() => addListItem("qualifications", emptyQualification())}
        error={hasSectionErrors("qualifications") ? "One or more qualification rows are incomplete." : undefined}
      >
        {data.qualifications.map((qualification, index) => (
          <div key={`qualification-${index}`} className="rounded-xl border p-4">
            <div className="mb-4 flex items-center justify-between">
              <h3 className="font-medium">Qualification {index + 1}</h3>
              {data.qualifications.length > 1 ? (
                <Button type="button" variant="outline" onClick={() => removeListItem("qualifications", index)}>
                  Remove
                </Button>
              ) : null}
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              <InputGroup label="Type" error={fieldError(`qualifications.${index}.qualification_type`)}>
                <select className="rounded-md border bg-card px-3 py-2 text-sm" value={qualification.qualification_type} onChange={(event) => setListItem("qualifications", index, "qualification_type", event.target.value)}>
                  <option value="">Select type</option>
                  {options.qualificationTypes.map((value) => (
                    <option key={value} value={value}>
                      {value}
                    </option>
                  ))}
                </select>
              </InputGroup>
              <InputGroup label="Institution" error={fieldError(`qualifications.${index}.institution`)}>
                <Input value={qualification.institution} onChange={(event) => setListItem("qualifications", index, "institution", event.target.value)} />
              </InputGroup>
              <InputGroup label="Qualification Name" error={fieldError(`qualifications.${index}.qualification_name`)}>
                <Input value={qualification.qualification_name} onChange={(event) => setListItem("qualifications", index, "qualification_name", event.target.value)} />
              </InputGroup>
              <InputGroup label="Field of Study" error={fieldError(`qualifications.${index}.field_of_study`)}>
                <Input value={qualification.field_of_study} onChange={(event) => setListItem("qualifications", index, "field_of_study", event.target.value)} />
              </InputGroup>
              <InputGroup label="NQF Level">
                <Input value={qualification.nqf_level} onChange={(event) => setListItem("qualifications", index, "nqf_level", event.target.value)} />
              </InputGroup>
              <InputGroup label="Completion Year">
                <Input value={qualification.completion_year} onChange={(event) => setListItem("qualifications", index, "completion_year", event.target.value)} />
              </InputGroup>
              <InputGroup label="Start Date">
                <Input type="date" value={qualification.start_date} onChange={(event) => setListItem("qualifications", index, "start_date", event.target.value)} />
              </InputGroup>
              <InputGroup label="End Date">
                <Input type="date" value={qualification.end_date} onChange={(event) => setListItem("qualifications", index, "end_date", event.target.value)} />
              </InputGroup>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={qualification.completed_flag} onChange={(event) => setListItem("qualifications", index, "completed_flag", event.target.checked)} />
                Completed
              </label>
            </div>
          </div>
        ))}
      </RepeaterCard>

      <RepeaterCard
        title="Skills Inventory"
        description="Track practical capabilities and proficiency levels."
        addLabel="Add Skill"
        onAdd={() => addListItem("skills", emptySkill())}
        error={hasSectionErrors("skills") ? "One or more skill rows are incomplete." : undefined}
      >
        {data.skills.map((skill, index) => (
          <div key={`skill-${index}`} className="grid gap-4 rounded-xl border p-4 md:grid-cols-4">
            <InputGroup label="Skill Name" error={fieldError(`skills.${index}.skill_name`)}>
              <Input value={skill.skill_name} onChange={(event) => setListItem("skills", index, "skill_name", event.target.value)} />
            </InputGroup>
            <InputGroup label="Category">
              <Input value={skill.category} onChange={(event) => setListItem("skills", index, "category", event.target.value)} />
            </InputGroup>
            <InputGroup label="Proficiency" error={fieldError(`skills.${index}.proficiency_level`)}>
              <select className="rounded-md border bg-card px-3 py-2 text-sm" value={skill.proficiency_level} onChange={(event) => setListItem("skills", index, "proficiency_level", event.target.value)}>
                <option value="">Select level</option>
                {options.skillLevels.map((value) => (
                  <option key={value} value={value}>
                    {value}
                  </option>
                ))}
              </select>
            </InputGroup>
            <InputGroup label="Years Experience">
              <div className="flex gap-2">
                <Input type="number" min="0" value={skill.years_experience} onChange={(event) => setListItem("skills", index, "years_experience", event.target.value)} />
                {data.skills.length > 1 ? (
                  <Button type="button" variant="outline" onClick={() => removeListItem("skills", index)}>
                    Remove
                  </Button>
                ) : null}
              </div>
            </InputGroup>
          </div>
        ))}
      </RepeaterCard>

      <RepeaterCard
        title="Work Experience"
        description="Support richer employment-readiness reporting."
        addLabel="Add Work Experience"
        onAdd={() => addListItem("work_experiences", emptyWorkExperience())}
        error={hasSectionErrors("work_experiences") ? "One or more work experience rows are incomplete." : undefined}
      >
        {data.work_experiences.map((experience, index) => (
          <div key={`experience-${index}`} className="rounded-xl border p-4">
            <div className="grid gap-4 md:grid-cols-3">
              <InputGroup label="Employer" error={fieldError(`work_experiences.${index}.employer`)}>
                <Input value={experience.employer} onChange={(event) => setListItem("work_experiences", index, "employer", event.target.value)} />
              </InputGroup>
              <InputGroup label="Position" error={fieldError(`work_experiences.${index}.position`)}>
                <Input value={experience.position} onChange={(event) => setListItem("work_experiences", index, "position", event.target.value)} />
              </InputGroup>
              <InputGroup label="Industry">
                <Input value={experience.industry} onChange={(event) => setListItem("work_experiences", index, "industry", event.target.value)} />
              </InputGroup>
              <InputGroup label="Start Date">
                <Input type="date" value={experience.start_date} onChange={(event) => setListItem("work_experiences", index, "start_date", event.target.value)} />
              </InputGroup>
              <InputGroup label="End Date">
                <Input type="date" value={experience.end_date} onChange={(event) => setListItem("work_experiences", index, "end_date", event.target.value)} />
              </InputGroup>
              <label className="flex items-center gap-2 text-sm">
                <input type="checkbox" checked={experience.current_employer_flag} onChange={(event) => setListItem("work_experiences", index, "current_employer_flag", event.target.checked)} />
                Current Employer
              </label>
              <InputGroup label="Responsibilities" className="md:col-span-3">
                <textarea className="min-h-24 rounded-md border bg-card px-3 py-2 text-sm" value={experience.responsibilities} onChange={(event) => setListItem("work_experiences", index, "responsibilities", event.target.value)} />
              </InputGroup>
            </div>
            {data.work_experiences.length > 1 ? (
              <div className="mt-4">
                <Button type="button" variant="outline" onClick={() => removeListItem("work_experiences", index)}>
                  Remove
                </Button>
              </div>
            ) : null}
          </div>
        ))}
      </RepeaterCard>

      <RepeaterCard
        title="Opportunity Interests"
        description="Prepare future matching to opportunities and placements."
        addLabel="Add Interest"
        onAdd={() => addListItem("interests", emptyInterest())}
        error={hasSectionErrors("interests") ? "One or more interest rows are incomplete." : undefined}
      >
        {data.interests.map((interest, index) => (
          <div key={`interest-${index}`} className="grid gap-4 rounded-xl border p-4 md:grid-cols-3">
            <InputGroup label="Interest Type" error={fieldError(`interests.${index}.interest_type`)}>
              <select className="rounded-md border bg-card px-3 py-2 text-sm" value={interest.interest_type} onChange={(event) => setListItem("interests", index, "interest_type", event.target.value)}>
                <option value="">Select type</option>
                {options.interestTypes.map((value) => (
                  <option key={value} value={value}>
                    {value}
                  </option>
                ))}
              </select>
            </InputGroup>
            <InputGroup label="Category">
              <Input value={interest.opportunity_category} onChange={(event) => setListItem("interests", index, "opportunity_category", event.target.value)} />
            </InputGroup>
            <InputGroup label="Notes">
              <div className="flex gap-2">
                <Input value={interest.notes} onChange={(event) => setListItem("interests", index, "notes", event.target.value)} />
                {data.interests.length > 1 ? (
                  <Button type="button" variant="outline" onClick={() => removeListItem("interests", index)}>
                    Remove
                  </Button>
                ) : null}
              </div>
            </InputGroup>
          </div>
        ))}
      </RepeaterCard>

      <RepeaterCard
        title="Organisational Assignments"
        description="Link members to governance, committees, branches, programmes, and projects."
        addLabel="Add Assignment"
        onAdd={() => addListItem("assignments", emptyAssignment())}
        error={hasSectionErrors("assignments") ? "One or more assignment rows are incomplete." : undefined}
      >
        {data.assignments.map((assignment, index) => (
          <div key={`assignment-${index}`} className="rounded-xl border p-4">
            <div className="grid gap-4 md:grid-cols-3">
              <InputGroup label="Assignment Type" error={fieldError(`assignments.${index}.assignment_type`)}>
                <select className="rounded-md border bg-card px-3 py-2 text-sm" value={assignment.assignment_type} onChange={(event) => setListItem("assignments", index, "assignment_type", event.target.value)}>
                  <option value="">Select type</option>
                  <option value="governance_structure">Governance Structure</option>
                  <option value="committee">Committee</option>
                  <option value="branch">Branch</option>
                  <option value="region">Region</option>
                  <option value="program">Programme</option>
                  <option value="project">Project</option>
                </select>
              </InputGroup>
              <InputGroup label="Assignable" error={fieldError(`assignments.${index}.assignable_id`)}>
                <select className="rounded-md border bg-card px-3 py-2 text-sm" value={assignment.assignable_id} onChange={(event) => setListItem("assignments", index, "assignable_id", event.target.value)}>
                  <option value="">Select record</option>
                  {assignmentChoices(assignment.assignment_type).map((option) => (
                    <option key={option.id} value={option.id}>
                      {option.name ?? option.title ?? `#${option.id}`}
                    </option>
                  ))}
                </select>
              </InputGroup>
              <InputGroup label="Member Role">
                <Input value={assignment.member_role} onChange={(event) => setListItem("assignments", index, "member_role", event.target.value)} />
              </InputGroup>
              <InputGroup label="Started At">
                <Input type="date" value={assignment.started_at} onChange={(event) => setListItem("assignments", index, "started_at", event.target.value)} />
              </InputGroup>
              <InputGroup label="Ended At">
                <Input type="date" value={assignment.ended_at} onChange={(event) => setListItem("assignments", index, "ended_at", event.target.value)} />
              </InputGroup>
              <InputGroup label="Notes">
                <div className="flex gap-2">
                  <Input value={assignment.notes} onChange={(event) => setListItem("assignments", index, "notes", event.target.value)} />
                  {data.assignments.length > 1 ? (
                    <Button type="button" variant="outline" onClick={() => removeListItem("assignments", index)}>
                      Remove
                    </Button>
                  ) : null}
                </div>
              </InputGroup>
            </div>
          </div>
        ))}
      </RepeaterCard>

      <div className="flex items-center justify-between">
        <Button type="button" variant="outline" asChild>
          <Link href="/members">Back to Registry</Link>
        </Button>
        <Button type="submit" disabled={processing}>
          {processing ? "Saving..." : mode === "create" ? "Register Member" : "Save Changes"}
        </Button>
      </div>
    </form>
  );
}

function RepeaterCard({
  title,
  description,
  addLabel,
  onAdd,
  error,
  children,
}: {
  title: string;
  description: string;
  addLabel: string;
  onAdd: () => void;
  error?: string;
  children: ReactNode;
}) {
  return (
    <Card>
      <CardHeader className="flex flex-row items-start justify-between gap-4">
        <div>
          <CardTitle>{title}</CardTitle>
          <CardDescription>{description}</CardDescription>
        </div>
        <Button type="button" variant="outline" onClick={onAdd}>
          {addLabel}
        </Button>
      </CardHeader>
      <CardContent className="space-y-4">
        {error ? <div className="rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">{error}</div> : null}
        {children}
      </CardContent>
    </Card>
  );
}
