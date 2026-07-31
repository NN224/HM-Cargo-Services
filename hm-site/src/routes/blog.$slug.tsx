import { createFileRoute, Link, notFound } from "@tanstack/react-router";
import { SiteNav } from "@/components/site-nav";
import { SiteFooter } from "@/components/site-footer";
import { getPost, relatedPosts, type BlogPost } from "@/lib/blog-data";
import { QuoteCTASection } from "@/components/home-sections";

export const Route = createFileRoute("/blog/$slug")({
  loader: ({ params }) => {
    const post = getPost(params.slug);
    if (!post) throw notFound();
    return { post, related: relatedPosts(params.slug) };
  },
  head: ({ params, loaderData }) => {
    if (!loaderData) {
      return { meta: [{ title: "Article — HM Cargo Services" }, { name: "robots", content: "noindex" }] };
    }
    const { post } = loaderData;
    return {
      meta: [
        { title: `${post.title} — HM Cargo Services` },
        { name: "description", content: post.excerpt },
        { property: "og:title", content: post.title },
        { property: "og:description", content: post.excerpt },
        { property: "og:type", content: "article" },
        { property: "og:url", content: `/blog/${params.slug}` },
        { property: "og:image", content: post.cover },
        { name: "twitter:image", content: post.cover },
      ],
      links: [{ rel: "canonical", href: `/blog/${params.slug}` }],
      scripts: [
        {
          type: "application/ld+json",
          children: JSON.stringify({
            "@context": "https://schema.org",
            "@graph": [
              {
                "@type": "Article",
                headline: post.title,
                description: post.excerpt,
                author: { "@type": "Organization", name: post.author },
                datePublished: post.publishedAt,
                image: post.cover,
              },
              {
                "@type": "BreadcrumbList",
                itemListElement: [
                  { "@type": "ListItem", position: 1, name: "Home", item: "/" },
                  { "@type": "ListItem", position: 2, name: "Blog", item: "/blog" },
                  { "@type": "ListItem", position: 3, name: post.title, item: `/blog/${params.slug}` },
                ],
              },
              {
                "@type": "FAQPage",
                mainEntity: post.faq.map((f) => ({
                  "@type": "Question",
                  name: f.q,
                  acceptedAnswer: { "@type": "Answer", text: f.a },
                })),
              },
            ],
          }),
        },
      ],
    };
  },
  component: ArticlePage,
  notFoundComponent: ArticleNotFound,
});

function ArticleNotFound() {
  return (
    <div className="bg-[#050607]">
      <SiteNav />
      <main id="main" className="pt-40 pb-28 mx-auto max-w-[900px] px-6 text-center">
        <p className="eyebrow">Article not found</p>
        <h1 className="mt-5 font-display text-5xl">This article isn't available.</h1>
        <Link to="/blog" className="btn-primary mt-8">Back to the blog</Link>
      </main>
      <SiteFooter />
    </div>
  );
}

function ArticlePage() {
  const { post, related } = Route.useLoaderData() as { post: BlogPost; related: BlogPost[] };
  const toc = post.body.filter((b): b is Extract<BlogPost["body"][number], { type: "h2" }> => b.type === "h2");


  return (
    <div className="bg-[#050607]">
      <SiteNav />
      <main id="main" className="pt-32">
        <nav aria-label="Breadcrumb" className="mx-auto max-w-[1200px] px-6 lg:px-10 py-6 text-xs tracking-[0.14em] uppercase text-[color:var(--text-dim)]">
          <Link to="/" className="hover:text-foreground">Home</Link>
          <span className="mx-2">/</span>
          <Link to="/blog" className="hover:text-foreground">Blog</Link>
          <span className="mx-2">/</span>
          <span className="text-muted-foreground">{post.category}</span>
        </nav>

        <header className="mx-auto max-w-[1000px] px-6 lg:px-10 py-10">
          <p className="text-[0.65rem] font-mono uppercase tracking-[0.2em] text-[color:var(--accent)]">{post.category}</p>
          <h1 className="mt-5 font-display text-5xl lg:text-7xl">{post.title}</h1>
          <p className="mt-6 max-w-2xl text-lg text-muted-foreground">{post.excerpt}</p>
          <div className="mt-8 flex flex-wrap gap-6 text-xs tracking-[0.14em] uppercase text-[color:var(--text-dim)]">
            <span>{post.author}</span>
            <span>{new Date(post.publishedAt).toLocaleDateString("en", { year: "numeric", month: "long", day: "numeric" })}</span>
            <span>{post.readingMinutes} min read</span>
          </div>
        </header>

        <div className="mx-auto max-w-[1200px] px-6 lg:px-10">
          <div className="aspect-[16/9] overflow-hidden border border-[color:var(--border)]">
            <img src={post.cover} alt="" className="h-full w-full object-cover" />
          </div>
        </div>

        <div className="mx-auto max-w-[1200px] px-6 lg:px-10 py-16 grid gap-12 lg:grid-cols-[1fr_2fr]">
          <aside className="lg:sticky lg:top-32 self-start">
            <p className="eyebrow">On this page</p>
            <ol className="mt-5 space-y-3 border-l border-[color:var(--border-strong)] pl-5">
              {toc.map((h, i) => (
                <li key={h.id}>
                  <a href={`#${h.id}`} className="text-sm text-muted-foreground hover:text-foreground">
                    <span className="font-mono text-[0.65rem] text-[color:var(--accent)] mr-3">{String(i + 1).padStart(2, "0")}</span>
                    {h.text}
                  </a>
                </li>
              ))}
            </ol>
          </aside>

          <article className="prose-editorial">
            {post.body.map((b, i) => {
              if (b.type === "h2") return <h2 key={i} id={b.id} className="font-display text-3xl lg:text-4xl mt-14 mb-6 scroll-mt-32">{b.text}</h2>;
              if (b.type === "p") return <p key={i} className="text-lg leading-[1.75] text-foreground/90 mb-6">{b.text}</p>;
              if (b.type === "ul") return (
                <ul key={i} className="my-8 space-y-3">
                  {b.items.map((it, j) => (
                    <li key={j} className="flex gap-4 text-lg leading-relaxed text-foreground/85">
                      <span className="mt-3 h-px w-6 bg-[color:var(--accent)] shrink-0" />
                      <span>{it}</span>
                    </li>
                  ))}
                </ul>
              );
              if (b.type === "quote") return (
                <blockquote key={i} className="my-12 border-l-2 border-[color:var(--accent)] pl-6 font-display text-2xl italic text-foreground">
                  {b.text}
                </blockquote>
              );
              return null;
            })}

            <section className="mt-20 border-t border-[color:var(--border)] pt-12">
              <p className="eyebrow">Frequently asked</p>
              <h2 className="mt-4 font-display text-3xl">Answers to the common questions.</h2>
              <div className="mt-8 space-y-4">
                {post.faq.map((f, i) => (
                  <details key={i} className="group border border-[color:var(--border)] bg-[#0b0d10] p-6 open:border-[color:var(--accent)]">
                    <summary className="cursor-pointer text-lg font-medium text-foreground list-none flex items-start justify-between gap-6">
                      {f.q}
                      <span className="text-[color:var(--accent)] transition-transform group-open:rotate-45 text-xl leading-none">+</span>
                    </summary>
                    <p className="mt-4 text-base leading-relaxed text-muted-foreground">{f.a}</p>
                  </details>
                ))}
              </div>
            </section>
          </article>
        </div>

        {related.length > 0 && (
          <section className="mx-auto max-w-[1440px] px-6 lg:px-10 py-16 border-t border-[color:var(--border)]">
            <p className="eyebrow">Related</p>
            <h2 className="mt-4 font-display text-3xl">Continue reading.</h2>
            <div className="mt-10 grid gap-8 md:grid-cols-3">
              {related.map((p) => (
                <Link key={p.slug} to="/blog/$slug" params={{ slug: p.slug }} className="group block border border-[color:var(--border)] bg-[#0b0d10] hover:border-[color:var(--accent)] transition-colors">
                  <div className="aspect-[16/10] overflow-hidden">
                    <img src={p.cover} alt="" className="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105" />
                  </div>
                  <div className="p-6">
                    <p className="text-[0.65rem] font-mono uppercase tracking-[0.2em] text-[color:var(--accent)]">{p.category}</p>
                    <h3 className="mt-3 font-display text-xl">{p.title}</h3>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        )}

        <QuoteCTASection />
      </main>
      <SiteFooter />
    </div>
  );
}
