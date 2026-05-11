import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const scriptDir = path.dirname(fileURLToPath(import.meta.url));
const repoRoot = path.resolve(scriptDir, '..');
const defaultDocsDir = path.resolve(
	repoRoot,
	'../wordpress-playground/packages/docs/site/docs'
);
const defaultOutFile = path.resolve(repoRoot, 'blueprints/personal-rag/content.xml');
const sourceRepoRoot = path.resolve(repoRoot, '../wordpress-playground');
const typedocModelFile = path.join(sourceRepoRoot, 'packages/docs/site/src/model.json');

const topLevelCategories = {
	main: { label: 'Documentation', slug: 'documentation' },
	developers: { label: 'Developers', slug: 'developers' },
	blueprints: { label: 'Blueprints', slug: 'blueprints' },
};

const fixedDate = '2026-05-11 00:00:00';
const fixedPubDate = 'Mon, 11 May 2026 00:00:00 +0000';
const siteUrl = 'https://playground.wordpress.net';
const rawStaticBase =
	'https://raw.githubusercontent.com/WordPress/wordpress-playground/refs/heads/trunk/packages/docs/site/static';

const args = parseArgs(process.argv.slice(2));
const docsDir = path.resolve(args.get('docs-dir') || defaultDocsDir);
const outFile = path.resolve(args.get('out') || defaultOutFile);

if (!fs.existsSync(docsDir)) {
	throw new Error(`Docs directory does not exist: ${docsDir}`);
}

const files = findMarkdownFiles(docsDir)
	.filter((file) => topLevelCategories[path.relative(docsDir, file).split(path.sep)[0]])
	.sort((a, b) => a.localeCompare(b));

const categories = new Map();
const posts = files.map((file, index) => buildPost(file, index + 1, categories));
const xml = buildWxr(posts, [...categories.values()]);

fs.mkdirSync(path.dirname(outFile), { recursive: true });
fs.writeFileSync(outFile, xml);

console.log(
	`Generated ${posts.length} posts and ${categories.size} categories in ${path.relative(
		repoRoot,
		outFile
	)}`
);

function parseArgs(values) {
	const parsed = new Map();
	for (let index = 0; index < values.length; index++) {
		const value = values[index];
		if (!value.startsWith('--')) {
			continue;
		}
		const [rawKey, rawValue] = value.slice(2).split('=');
		if (rawValue !== undefined) {
			parsed.set(rawKey, rawValue);
		} else {
			parsed.set(rawKey, values[index + 1]);
			index++;
		}
	}
	return parsed;
}

function findMarkdownFiles(dir) {
	const entries = fs.readdirSync(dir, { withFileTypes: true });
	let files = [];

	for (const entry of entries) {
		if (entry.name === '_fragments' || entry.name === 'node_modules') {
			continue;
		}

		const fullPath = path.join(dir, entry.name);
		if (entry.isDirectory()) {
			files = files.concat(findMarkdownFiles(fullPath));
		} else if (entry.isFile() && entry.name.endsWith('.md')) {
			files.push(fullPath);
		}
	}

	return files;
}

function buildPost(file, postId, categories) {
	const relativeFile = path.relative(docsDir, file).replaceAll(path.sep, '/');
	const source = fs.readFileSync(file, 'utf8');
	const { frontmatter, body } = splitFrontmatter(source);
	const title = frontmatter.title || firstHeading(body) || titleFromFile(file);
	const postName = postSlug(frontmatter.slug, relativeFile);
	const sourceUrl = sourceUrlFor(frontmatter.slug, relativeFile);
	const categoryChain = ensureCategories(relativeFile, categories);
	const content = markdownToHtml(normalizeMdx(body, relativeFile));
	const description = frontmatter.description || '';

	return {
		id: postId,
		title,
		postName,
		link: `${siteUrl}/${postName}/`,
		guid: `${siteUrl}/?p=${postId}`,
		content: [
			content,
			`<hr><p><strong>Original Playground docs source:</strong> <a href="${escapeHtml(
				sourceUrl
			)}">${escapeHtml(sourceUrl)}</a></p>`,
		].join('\n'),
		excerpt: description,
		categories: categoryChain,
		sourceFile: relativeFile,
		sourceSlug: frontmatter.slug || '',
	};
}

function splitFrontmatter(source) {
	if (!source.startsWith('---\n')) {
		return { frontmatter: {}, body: source };
	}

	const end = source.indexOf('\n---', 4);
	if (end === -1) {
		return { frontmatter: {}, body: source };
	}

	const raw = source.slice(4, end);
	const body = source.slice(source.indexOf('\n', end + 4) + 1);
	const frontmatter = {};

	for (const line of raw.split('\n')) {
		const match = line.match(/^([A-Za-z0-9_-]+):\s*(.*)$/);
		if (!match) {
			continue;
		}
		frontmatter[match[1]] = unquote(match[2].trim());
	}

	return { frontmatter, body };
}

function unquote(value) {
	if (
		(value.startsWith('"') && value.endsWith('"')) ||
		(value.startsWith("'") && value.endsWith("'"))
	) {
		return value.slice(1, -1);
	}
	return value;
}

function firstHeading(body) {
	const heading = body.match(/^#\s+(.+)$/m);
	return heading ? stripMarkdown(heading[1]).trim() : '';
}

function titleFromFile(file) {
	const base = path.basename(file, '.md');
	return labelFromSegment(base);
}

function postSlug(frontmatterSlug, relativeFile) {
	if (frontmatterSlug && frontmatterSlug !== '/') {
		return slugify(frontmatterSlug.replace(/^\/+|\/+$/g, '').replaceAll('/', '-'));
	}
	if (frontmatterSlug === '/') {
		return 'wordpress-playground-docs';
	}
	return slugify(relativeFile.replace(/\.md$/, '').replaceAll('/', '-'));
}

function sourceUrlFor(frontmatterSlug, relativeFile) {
	if (frontmatterSlug) {
		return `${siteUrl}${frontmatterSlug.startsWith('/') ? frontmatterSlug : `/${frontmatterSlug}`}`;
	}
	return `${siteUrl}/${relativeFile.replace(/\.md$/, '')}`;
}

function ensureCategories(relativeFile, categories) {
	const segments = relativeFile.split('/');
	const top = segments[0];
	const folderSegments = segments.slice(1, -1);
	const chain = [];
	const topCategory = topLevelCategories[top];

	let parentKey = '';
	let currentKey = top;
	let currentSlug = topCategory.slug;
	ensureCategory(categories, currentKey, {
		label: topCategory.label,
		slug: currentSlug,
		parentSlug: '',
	});
	chain.push(categories.get(currentKey));

	for (const segment of folderSegments) {
		parentKey = currentKey;
		currentKey = `${currentKey}/${segment}`;
		currentSlug = `${categories.get(parentKey).slug}-${slugify(stripNumberPrefix(segment))}`;
		ensureCategory(categories, currentKey, {
			label: categoryLabelFor(path.join(docsDir, currentKey)),
			slug: currentSlug,
			parentSlug: categories.get(parentKey).slug,
		});
		chain.push(categories.get(currentKey));
	}

	return chain;
}

function ensureCategory(categories, key, category) {
	if (!categories.has(key)) {
		categories.set(key, {
			id: categories.size + 1,
			...category,
		});
	}
}

function categoryLabelFor(dir) {
	const categoryFile = path.join(dir, '_category_.json');
	if (fs.existsSync(categoryFile)) {
		try {
			const data = JSON.parse(fs.readFileSync(categoryFile, 'utf8'));
			if (data.label) {
				return data.label;
			}
		} catch (error) {
			throw new Error(`Could not parse ${categoryFile}: ${error.message}`);
		}
	}
	return labelFromSegment(path.basename(dir));
}

function normalizeMdx(markdown, relativeFile) {
	const lines = markdown.replace(/\r\n?/g, '\n').split('\n');
	const normalized = [];
	let inFence = false;
	let skipImport = false;
	let componentBlock = null;

	for (const line of lines) {
		const trimmed = line.trim();

		if (trimmed.startsWith('```')) {
			inFence = !inFence;
			normalized.push(line);
			continue;
		}

		if (inFence) {
			normalized.push(line);
			continue;
		}

		if (skipImport) {
			if (trimmed.endsWith(';')) {
				skipImport = false;
			}
			continue;
		}

		if (componentBlock) {
			componentBlock.lines.push(line);
			if (trimmed.endsWith('/>') || trimmed === '</span>') {
				flushComponentBlock(componentBlock, normalized, relativeFile);
				componentBlock = null;
			}
			continue;
		}

		if (/^import\b/.test(trimmed)) {
			if (!trimmed.endsWith(';')) {
				skipImport = true;
			}
			continue;
		}

		if (/^<(BlueprintExample|UpdateTopLevelToc|BlueprintStep|TSDocstring|TOCInline)\b/.test(trimmed)) {
			if (trimmed.startsWith('<TOCInline') || trimmed.startsWith('<TSDocstring')) {
				continue;
			}
			componentBlock = { name: trimmed.match(/^<([A-Za-z0-9]+)/)[1], lines: [line] };
			if (trimmed.endsWith('/>')) {
				flushComponentBlock(componentBlock, normalized, relativeFile);
				componentBlock = null;
			}
			continue;
		}

		if (trimmed === '<span>') {
			componentBlock = { name: 'InteractiveDocs', lines: [line] };
			continue;
		}

		if (trimmed === '</span>') {
			continue;
		}

		if (/^<div\b/i.test(trimmed) || /^<\/div>$/i.test(trimmed) || /^<p\b[^>]*>\s*<\/p>$/i.test(trimmed)) {
			continue;
		}

		if (/^<br\s*\/?>/i.test(trimmed)) {
			continue;
		}

		if (/^<iframe\b/i.test(trimmed)) {
			const src = trimmed.match(/\bsrc=["']([^"']+)["']/i)?.[1];
			normalized.push(src ? `Embedded media: ${src}` : 'Embedded media omitted.');
			continue;
		}

		if (/^<script\b/i.test(trimmed) || /^<\/script>$/i.test(trimmed) || /^<php-snippet\b/i.test(trimmed) || /^<\/php-snippet>$/i.test(trimmed)) {
			normalized.push(`\`${trimmed}\``);
			continue;
		}

		const admonition = trimmed.match(/^:{3,4}([A-Za-z]+)?(?:\s*(.*))?$/);
		if (admonition) {
			if (admonition[1]) {
				const label = labelFromSegment(admonition[1]);
				const title = admonition[2] ? `: ${admonition[2]}` : '';
				normalized.push(`> ${label}${title}`);
			}
			continue;
		}

		normalized.push(line);
	}

	return normalized.join('\n').trim();
}

function flushComponentBlock(block, output, relativeFile) {
	if (block.name === 'BlueprintExample') {
		output.push('');
		output.push('```jsx');
		output.push(...block.lines);
		output.push('```');
		output.push('');
		return;
	}

	if (block.name === 'UpdateTopLevelToc') {
		return;
	}

	if (block.name === 'InteractiveDocs' && relativeFile === 'blueprints/05-steps.md') {
		output.push('');
		output.push(buildBlueprintStepsReference());
		output.push('');
		return;
	}

	if (block.name === 'BlueprintStep' || block.name === 'InteractiveDocs') {
		output.push('');
		output.push('This section renders interactive reference content in the official documentation.');
		output.push('');
	}
}

function buildBlueprintStepsReference() {
	if (!fs.existsSync(typedocModelFile)) {
		return 'The interactive Blueprint step reference is generated from TypeDoc in the official documentation.';
	}

	const model = JSON.parse(fs.readFileSync(typedocModelFile, 'utf8'));
	const blueprintsApi = findModule(model, '@wp-playground/blueprints');
	if (!blueprintsApi?.children) {
		return 'The interactive Blueprint step reference is generated from TypeDoc in the official documentation.';
	}

	const steps = blueprintsApi.children
		.filter((entry) => entry.name?.match(/Step$/))
		.filter(
			(entry) =>
				!['CompiledStep', 'CompiledV1Step', 'GenericStep', 'Step'].includes(
					entry.name
				)
		)
		.filter((entry) => !entry.flags?.isPrivate)
		.sort((a, b) => a.name.localeCompare(b.name));

	const blocks = ['## Blueprint Step Reference'];
	for (const step of steps) {
		const props = step.children || step.type?.declaration?.children || [];
		const stepId = props.find((prop) => prop.name === 'step')?.type?.value || step.name;
		const summary = commentText(step.comment?.summary);
		const params = props.filter((prop) => prop.name !== 'step');
		const example = firstExample(step);

		blocks.push(`### ${stepId}`);
		if (summary) {
			blocks.push(summary);
		}
		if (params.length) {
			blocks.push('Parameters:');
			for (const param of params) {
				const optional = param.flags?.isOptional ? ' optional' : '';
				const type = typeLabel(param.type);
				const description = commentText(param.comment?.summary);
				blocks.push(
					`- \`${param.name}\`${type ? ` (${type}${optional})` : optional ? ` (${optional.trim()})` : ''}${description ? `: ${description}` : ''}`
				);
			}
		}
		if (example) {
			blocks.push('Example:');
			blocks.push('```json');
			blocks.push(example);
			blocks.push('```');
		}
	}

	return blocks.join('\n\n');
}

function findModule(node, name) {
	if (node?.name === name) {
		return node;
	}
	for (const child of node?.children || []) {
		const found = findModule(child, name);
		if (found) {
			return found;
		}
	}
	return null;
}

function commentText(parts = []) {
	return parts
		.map((part) => {
			if (part.kind === 'code') {
				return `\`${part.text.replace(/^`|`$/g, '')}\``;
			}
			return part.text || '';
		})
		.join('')
		.replace(/\s+/g, ' ')
		.trim();
}

function firstExample(step) {
	const tag = step.comment?.blockTags?.find((entry) => entry.tag === '@example');
	const raw = tag?.content?.[0]?.text;
	if (!raw) {
		return '';
	}
	return raw
		.trim()
		.replace(/^```[a-z]*\s*/i, '')
		.replace(/```$/i, '')
		.trim()
		.replace(/^<code>\s*/i, '')
		.replace(/\s*<\/code>$/i, '')
		.trim();
}

function typeLabel(type) {
	if (!type) {
		return '';
	}
	if (type.name) {
		return type.name;
	}
	if (type.value !== undefined) {
		return JSON.stringify(type.value);
	}
	if (type.type === 'array') {
		return `${typeLabel(type.elementType)}[]`;
	}
	if (type.type === 'union') {
		return type.types.map(typeLabel).filter(Boolean).join(' | ');
	}
	if (type.type === 'reflection') {
		return 'object';
	}
	if (type.type === 'intrinsic') {
		return type.name || '';
	}
	return type.type || '';
}

function markdownToHtml(markdown) {
	const lines = markdown.split('\n');
	const html = [];
	let index = 0;

	while (index < lines.length) {
		const line = lines[index];
		const trimmed = line.trim();

		if (!trimmed) {
			index++;
			continue;
		}

		const fence = trimmed.match(/^```\s*([A-Za-z0-9_-]+)?/);
		if (fence) {
			const code = [];
			index++;
			while (index < lines.length && !lines[index].trim().startsWith('```')) {
				code.push(lines[index]);
				index++;
			}
			if (index < lines.length) {
				index++;
			}
			html.push(`<pre><code>${escapeHtml(code.join('\n'))}</code></pre>`);
			continue;
		}

		const heading = trimmed.match(/^(#{1,6})\s+(.+)$/);
		if (heading) {
			const level = Math.min(6, heading[1].length);
			html.push(`<h${level}>${inlineMarkdown(heading[2])}</h${level}>`);
			index++;
			continue;
		}

		if (/^>\s?/.test(trimmed)) {
			const quote = [];
			while (index < lines.length && /^>\s?/.test(lines[index].trim())) {
				quote.push(lines[index].trim().replace(/^>\s?/, ''));
				index++;
			}
			html.push(`<blockquote>${markdownToHtml(quote.join('\n'))}</blockquote>`);
			continue;
		}

		if (/^\s*[-*+]\s+/.test(line)) {
			const items = [];
			while (index < lines.length && /^\s*[-*+]\s+/.test(lines[index])) {
				items.push(lines[index].replace(/^\s*[-*+]\s+/, ''));
				index++;
			}
			html.push(`<ul>${items.map((item) => `<li>${inlineMarkdown(item)}</li>`).join('')}</ul>`);
			continue;
		}

		if (/^\s*\d+[.)]\s+/.test(line)) {
			const items = [];
			while (index < lines.length && /^\s*\d+[.)]\s+/.test(lines[index])) {
				items.push(lines[index].replace(/^\s*\d+[.)]\s+/, ''));
				index++;
			}
			html.push(`<ol>${items.map((item) => `<li>${inlineMarkdown(item)}</li>`).join('')}</ol>`);
			continue;
		}

		if (/^\|.*\|$/.test(trimmed)) {
			const rows = [];
			while (index < lines.length && /^\|.*\|$/.test(lines[index].trim())) {
				rows.push(lines[index]);
				index++;
			}
			html.push(`<pre><code>${escapeHtml(rows.join('\n'))}</code></pre>`);
			continue;
		}

		const paragraph = [trimmed];
		index++;
		while (
			index < lines.length &&
			lines[index].trim() &&
			!isBlockStart(lines[index].trim())
		) {
			paragraph.push(lines[index].trim());
			index++;
		}
		html.push(`<p>${inlineMarkdown(paragraph.join(' '))}</p>`);
	}

	return html.join('\n');
}

function isBlockStart(line) {
	return /^#{1,6}\s+/.test(line) ||
		line.startsWith('```') ||
		/^>\s?/.test(line) ||
		/^\s*[-*+]\s+/.test(line) ||
		/^\s*\d+[.)]\s+/.test(line) ||
		/^\|.*\|$/.test(line);
}

function inlineMarkdown(value) {
	let html = escapeHtml(value);
	html = html.replace(/!\[([^\]]*)\]\(([^)]+)\)/g, (_match, alt, url) => {
		return `<figure><img src="${escapeHtml(resolveAssetUrl(url))}" alt="${escapeHtml(
			alt
		)}"></figure>`;
	});
	html = html.replace(/\[([^\]]+)\]\(([^)]+)\)/g, (_match, text, url) => {
		return `<a href="${escapeHtml(resolveLinkUrl(url))}">${text}</a>`;
	});
	html = html.replace(/`([^`]+)`/g, '<code>$1</code>');
	html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
	return html;
}

function buildWxr(posts, categories) {
	const categoryXml = categories
		.sort((a, b) => a.id - b.id)
		.map(
			(category) => `	<wp:category>
		<wp:term_id>${category.id}</wp:term_id>
		<wp:category_nicename><![CDATA[${category.slug}]]></wp:category_nicename>
		<wp:category_parent><![CDATA[${category.parentSlug}]]></wp:category_parent>
		<wp:cat_name><![CDATA[${safeCdata(category.label)}]]></wp:cat_name>
	</wp:category>`
		)
		.join('\n');

	const itemXml = posts.map(postToWxrItem).join('\n\n');

	return `<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
	xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:wp="http://wordpress.org/export/1.2/"
>
<channel>
	<title>WordPress Playground Docs for Personal RAG</title>
	<link>${siteUrl}</link>
	<description>Starter documentation corpus for the Personal RAG blueprint.</description>
	<pubDate>${fixedPubDate}</pubDate>
	<language>en-US</language>
	<wp:wxr_version>1.2</wp:wxr_version>
	<wp:base_site_url>${siteUrl}</wp:base_site_url>
	<wp:base_blog_url>${siteUrl}</wp:base_blog_url>
	<wp:author>
		<wp:author_id>1</wp:author_id>
		<wp:author_login><![CDATA[admin]]></wp:author_login>
		<wp:author_email><![CDATA[admin@example.com]]></wp:author_email>
		<wp:author_display_name><![CDATA[admin]]></wp:author_display_name>
		<wp:author_first_name><![CDATA[]]></wp:author_first_name>
		<wp:author_last_name><![CDATA[]]></wp:author_last_name>
	</wp:author>
${categoryXml}

${itemXml}
</channel>
</rss>
`;
}

function postToWxrItem(post) {
	const categories = post.categories
		.map(
			(category) =>
				`		<category domain="category" nicename="${escapeAttr(category.slug)}"><![CDATA[${safeCdata(
					category.label
				)}]]></category>`
		)
		.join('\n');

	return `	<item>
		<title><![CDATA[${safeCdata(post.title)}]]></title>
		<link>${escapeHtml(post.link)}</link>
		<pubDate>${fixedPubDate}</pubDate>
		<dc:creator><![CDATA[admin]]></dc:creator>
		<guid isPermaLink="false">${escapeHtml(post.guid)}</guid>
		<description></description>
		<content:encoded><![CDATA[${safeCdata(post.content)}]]></content:encoded>
		<excerpt:encoded><![CDATA[${safeCdata(post.excerpt)}]]></excerpt:encoded>
		<wp:post_id>${post.id}</wp:post_id>
		<wp:post_date><![CDATA[${fixedDate}]]></wp:post_date>
		<wp:post_date_gmt><![CDATA[${fixedDate}]]></wp:post_date_gmt>
		<wp:post_modified><![CDATA[${fixedDate}]]></wp:post_modified>
		<wp:post_modified_gmt><![CDATA[${fixedDate}]]></wp:post_modified_gmt>
		<wp:comment_status><![CDATA[closed]]></wp:comment_status>
		<wp:ping_status><![CDATA[closed]]></wp:ping_status>
		<wp:post_name><![CDATA[${safeCdata(post.postName)}]]></wp:post_name>
		<wp:status><![CDATA[publish]]></wp:status>
		<wp:post_parent>0</wp:post_parent>
		<wp:menu_order>0</wp:menu_order>
		<wp:post_type><![CDATA[post]]></wp:post_type>
		<wp:post_password><![CDATA[]]></wp:post_password>
		<wp:is_sticky>0</wp:is_sticky>
${categories}
		<wp:postmeta>
			<wp:meta_key><![CDATA[_personal_rag_seed_source]]></wp:meta_key>
			<wp:meta_value><![CDATA[${safeCdata(post.sourceFile)}]]></wp:meta_value>
		</wp:postmeta>
		<wp:postmeta>
			<wp:meta_key><![CDATA[_personal_rag_docs_slug]]></wp:meta_key>
			<wp:meta_value><![CDATA[${safeCdata(post.sourceSlug)}]]></wp:meta_value>
		</wp:postmeta>
	</item>`;
}

function resolveAssetUrl(url) {
	if (/^[a-z]+:\/\//i.test(url)) {
		return url;
	}
	if (url.startsWith('/img/')) {
		return `${rawStaticBase}${url}`;
	}
	return url;
}

function resolveLinkUrl(url) {
	if (/^[a-z]+:\/\//i.test(url) || url.startsWith('#') || url.startsWith('mailto:')) {
		return url;
	}
	if (url.startsWith('/')) {
		return `${siteUrl}${url}`;
	}
	return url;
}

function labelFromSegment(segment) {
	const words = stripNumberPrefix(segment).replace(/[_-]+/g, ' ').trim().split(/\s+/);
	return words.map(titleWord).join(' ');
}

function titleWord(word) {
	const lower = word.toLowerCase();
	const acronyms = new Map([
		['api', 'API'],
		['apis', 'APIs'],
		['cli', 'CLI'],
		['css', 'CSS'],
		['html', 'HTML'],
		['ios', 'iOS'],
		['json', 'JSON'],
		['php', 'PHP'],
		['pr', 'PR'],
		['wasm', 'WASM'],
		['wp', 'WP'],
		['wpcli', 'WP-CLI'],
		['xdebug', 'Xdebug'],
	]);
	return acronyms.get(lower) || lower.charAt(0).toUpperCase() + lower.slice(1);
}

function stripNumberPrefix(value) {
	return value.replace(/^\d+-/, '');
}

function slugify(value) {
	return stripNumberPrefix(String(value))
		.toLowerCase()
		.replace(/&/g, ' and ')
		.replace(/[^a-z0-9]+/g, '-')
		.replace(/^-+|-+$/g, '') || 'doc';
}

function stripMarkdown(value) {
	return value
		.replace(/!\[([^\]]*)\]\([^)]+\)/g, '$1')
		.replace(/\[([^\]]+)\]\([^)]+\)/g, '$1')
		.replace(/[`*_#]/g, '');
}

function safeCdata(value) {
	return String(value || '').replaceAll(']]>', ']]]]><![CDATA[>');
}

function escapeHtml(value) {
	return String(value || '')
		.replaceAll('&', '&amp;')
		.replaceAll('<', '&lt;')
		.replaceAll('>', '&gt;')
		.replaceAll('"', '&quot;')
		.replaceAll("'", '&#039;');
}

function escapeAttr(value) {
	return escapeHtml(value);
}
