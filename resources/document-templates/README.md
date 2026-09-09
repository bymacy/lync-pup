# Export document masters

These 13 `.docx` files are the real master templates used by
`App\Services\Exports\WordDocumentExporter` to generate every Word/PDF
export in the Assessment Hub. Unlike `storage/app/`, this folder under
`resources/` is **tracked by git**, so cloning the repo brings these
files along automatically — no manual zip/team-drive step needed.

Filenames must match exactly (see `TEMPLATES` in
`WordDocumentExporter.php`) — don't rename them:

- `startup-information-sheet-template.docx`
- `startup-tech-assessment-trl-template.docx`
- `startup-tech-assessment-mrl-template.docx`
- `startup-tech-assessment-tmrl-template.docx`
- `startup-tech-assessment-srl-template.docx`
- `startup-post-tech-assessment-trl-template.docx`
- `startup-post-tech-assessment-mrl-template.docx`
- `startup-post-tech-assessment-tmrl-template.docx`
- `startup-post-tech-assessment-srl-template.docx`
- `doc6-startup-growth-strategy-template.docx`
- `doc7-weekly-checkins-template.docx`
- `doc8-prototype-validation-template.docx`
- `doc13-venture-exit-template.docx`
