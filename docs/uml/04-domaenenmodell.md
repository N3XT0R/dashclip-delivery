# Domänenmodell

Ein Angebot heißt im Code `Assignment`: ein Video, das einem Kanal in einem Lauf (`Batch`) angeboten
wird.

## Kern: Videos, Kanäle und Angebote

```mermaid
classDiagram
    direction LR

    class Video {
        +id
        +hash
        +original_name
        +path
        +disk
        +processing_status: ProcessingStatusEnum
        +deleted_at
    }
    class Clip {
        +start_sec
        +end_sec
        +note
        +bundle_key
        +preview_path
    }
    class Assignment {
        +status: StatusEnum
        +expires_at
        +last_notified_at
        +download_token
        +via_preferred_channel
    }
    class Batch {
        +type: BatchTypeEnum
        +started_at
        +finished_at
        +stats
    }
    class Channel {
        +name
        +email
        +youtube_name
        +weight
        +weekly_quota
        +is_video_reception_paused
        +show_on_homepage
    }
    class Download {
        +ip
        +user_agent
        +downloaded_at
    }
    class ChannelVideoBlock {
        +until
    }
    class OfferLinkClick
    class ChannelConfig
    class Team
    class User
    class ChannelApplication {
        +status: ApplicationEnum
    }

    Video "1" --> "*" Clip : clips
    Video "1" --> "*" Assignment : assignments
    Video "*" --> "0..1" Team : team
    Clip "*" --> "0..1" User : user
    Clip "*" --> "0..1" Channel : preferredChannel
    Assignment "*" --> "1" Channel : channel
    Assignment "*" --> "1" Batch : batch
    Assignment "1" --> "*" Download : downloads
    Batch "*" --> "*" Channel : channels
    Channel "1" --> "*" ChannelVideoBlock : videoBlocks
    ChannelVideoBlock "*" --> "1" Video : video
    Channel "1" --> "*" ChannelConfig : config
    Channel "*" --> "*" Team : assignedTeams
    Channel "*" --> "*" User : channelUsers
    OfferLinkClick "*" --> "1" Batch
    OfferLinkClick "*" --> "1" Channel
    OfferLinkClick "*" --> "0..1" User
    Team "*" --> "*" User : users
    Team "*" --> "1" User : owner
    User "1" --> "*" ChannelApplication : channelApplications
    ChannelApplication "*" --> "0..1" Channel : channel
```

## Benutzer, Rechte und Kommunikation

```mermaid
classDiagram
    direction LR

    class User {
        +name
        +email
        +last_login_at
    }
    class Team
    class Role {
        <<RoleEnum>>
        super_admin
        panel_user
        channel_operator
    }
    class Permission
    class Passkey
    class UserMailConfig
    class ActionToken {
        +purpose: TokenPurposeEnum
        +token_hash
        +expires_at
        +used_at
    }
    class MailLog {
        +direction: MailDirection
        +status: MailStatus
    }
    class Activity
    class Config {
        +key
        +value
        +cast_type: ConfigTypeEnum
    }
    class ConfigCategory

    User "*" --> "*" Team : teams
    User "*" --> "*" Role : teamRoles
    Role "*" --> "*" Permission
    Role "*" --> "*" Team : teams
    User "1" --> "*" Passkey
    User "1" --> "*" UserMailConfig : mailConfigs
    ActionToken --> "1" ChannelApplication : subject (morph)
    Config "*" --> "1" ConfigCategory : category
    Activity ..> User : causer
```

## Blog

```mermaid
classDiagram
    direction LR
    class Post
    class PostTranslation {
        +locale
        +title
        +slug
        +status: PostStatusEnum
        +published_at
    }
    class PostCategory
    class PostCategoryTranslation
    class PostTag
    class PostTagTranslation

    Post "1" --> "*" PostTranslation : translations
    Post "*" --> "1" User : author
    Post "*" --> "0..1" PostCategory : category
    Post "*" --> "*" PostTag : tags
    PostCategory "1" --> "*" PostCategoryTranslation
    PostTag "1" --> "*" PostTagTranslation
```

Die Attributlisten zeigen die fachlich wichtigen Felder, nicht jede Spalte.
