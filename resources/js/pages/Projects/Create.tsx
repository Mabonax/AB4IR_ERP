import { ProjectFormPage } from "@/components/project-form-page";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Projects", href: "/projects" },
  { title: "List", href: "/projects/list" },
  { title: "Create", href: "/projects/create" },
];

export default function ProjectCreate(props: {
  programs: { id: number; title: string }[];
  stakeholders: { id: number; name: string }[];
  partnerStakeholders: { id: number; name: string }[];
  staffMembers: { id: number; name: string }[];
}) {
  return (
    <ProjectFormPage
      pageTitle="Create Project"
      pageDescription="Capture the delivery structure, governance metadata, and reporting obligations for a new project."
      submitLabel="Create Project"
      submitMethod="post"
      submitUrl="/projects"
      breadcrumbs={breadcrumbs}
      {...props}
    />
  );
}
